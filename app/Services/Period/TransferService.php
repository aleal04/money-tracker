<?php

namespace App\Services\Period;

use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
use App\Constant\CategoryConstant;
use App\Models\Period\PeriodModel;
use App\Constant\LineSourceConstant;
use App\Helpers\PeriodScheduleHelper;
use App\Repositories\Period\LineRepository;
use App\Repositories\Period\LineQueryRepository;
use App\Repositories\Space\SpaceQueryRepository;
use App\Repositories\Period\PeriodQueryRepository;
use App\Contracts\Services\Period\TransferServiceContract;

class TransferService implements TransferServiceContract
{
    /**
     * The most periods a transfer may create in the destination space to reach its date.
     */
    private const MAX_CREATED_PERIODS = 12;

    /**
     * Create the transfer service with its dependencies.
     *
     * @param  LineRepository  $lineRepository  The line writes.
     * @param  LineQueryRepository  $lineQueryRepository  The line reads.
     * @param  PeriodQueryRepository  $periodQueryRepository  The period reads.
     * @param  SpaceQueryRepository  $spaceQueryRepository  The space reads.
     * @param  PeriodService  $periodService  The period creation.
     */
    public function __construct(
        private readonly LineRepository $lineRepository,
        private readonly LineQueryRepository $lineQueryRepository,
        private readonly PeriodQueryRepository $periodQueryRepository,
        private readonly SpaceQueryRepository $spaceQueryRepository,
        private readonly PeriodService $periodService,
    ) {}

    /**
     * The 422 response when a line or template cannot send money to the given space, or null when it can.
     *
     * @param  int|null  $targetSpaceId  The target space identifier, or null for no transfer.
     * @param  string  $category  The category of the line or template.
     * @param  int  $ownSpaceId  The space the line or template belongs to.
     * @return object|null The error response, or null when the target is allowed.
     */
    public function targetError(?int $targetSpaceId, string $category, int $ownSpaceId): ?object
    {
        $message = match (true) {
            $targetSpaceId === null => null,
            $category === CategoryConstant::INGRESO => 'Solo un egreso se puede enviar a otro espacio',
            $targetSpaceId === $ownSpaceId => 'No se puede enviar al mismo espacio',
            $this->spaceQueryRepository->findSpace($targetSpaceId) === null => 'Espacio inválido',
            default => null,
        };

        return $message === null ? null : createValidationErrorResponse($message, ['statusCode' => 422, 'errors' => ['targetSpaceId' => [$message]]]);
    }

    /**
     * Validate the target of a line that was just written and make its destination line match it: a live, paid,
     * not carried line with a target has one paid ingreso line in the target space's period that contains the start
     * date of the line's period; any other line has none. Runs inside the transaction of the write.
     *
     * @param  LineModel  $line  The line after the write (it may be logically deleted).
     * @return object|null The 409/422 error response, or null when everything is in place.
     */
    public function sync(LineModel $line): ?object
    {
        $period = $this->periodQueryRepository->find((int) $line->FkPeriod);
        $targetSpaceId = $line->FkTargetSpace === null ? null : (int) $line->FkTargetSpace;
        $sends = ! $line->IsDeleted && $line->Paid && $targetSpaceId !== null && $line->FkCarriedToLine === null;
        $error = $line->IsDeleted ? null : $this->targetError($targetSpaceId, $line->Category, (int) $period->FkSpace);
        if ($error !== null) {
            return $error;
        }

        $destination = $this->lineQueryRepository->findTransferOf($line->IdLine, true);
        if (! $sends) {
            if ($destination !== null) {
                $this->lineRepository->remove($destination);
            }

            return null;
        }

        $amount = $line->Actual ?? $line->Estimated;
        $columns = ['Name' => mb_substr('Desde '.$period->space->Name.': '.$line->Name, 0, 255), 'Estimated' => $amount, 'Actual' => $amount];
        if ($destination !== null && (int) $destination->period->FkSpace === $targetSpaceId) {
            $this->lineRepository->updateColumns($destination, $columns);

            return null;
        }
        if ($destination !== null) {
            $this->lineRepository->remove($destination);
        }

        $target = $this->spaceQueryRepository->findSpace($targetSpaceId);
        $destinationPeriod = $this->periodContaining($target, $period->StartDate);
        if (is_string($destinationPeriod)) {
            return createValidationErrorResponse($destinationPeriod, ['statusCode' => 409]);
        }
        $this->lineRepository->insert($columns + [
            'FkPeriod' => $destinationPeriod->IdPeriod,
            'Category' => CategoryConstant::INGRESO,
            'Paid' => true,
            'PaidAt' => $line->PaidAt ?? now(),
            'Source' => LineSourceConstant::TRANSFER,
            'FkTransferFromLine' => $line->IdLine,
        ]);

        return null;
    }

    /**
     * The period of the space that contains the date, creating it when needed: the first period aligned to the
     * frequency when the space has none, the missing periods after its latest one, or the periods stepped back from
     * its first one (at most 12 either way). A date in a gap between periods is not created.
     *
     * @param  SpaceModel  $space  The destination space.
     * @param  string  $date  The date 'Y-m-d'.
     * @return PeriodModel|string The period, or the 409 message when it must be created by hand.
     */
    private function periodContaining(SpaceModel $space, string $date): PeriodModel|string
    {
        $existing = $this->periodQueryRepository->findOverlapping($space->IdSpace, $date, $date);
        if ($existing !== null) {
            return $existing;
        }

        $latest = $this->periodQueryRepository->latest($space->IdSpace, true);
        $first = $latest === null ? null : $this->periodQueryRepository->earliest($space->IdSpace);
        if ($first !== null && $date < $first->StartDate) {
            return $this->createBackward($space, $first->StartDate, $date);
        }
        if ($latest !== null && $date <= $latest->EndDate) {
            return 'Creá primero el periodo en '.$space->Name;
        }

        $chain = [];
        $lastEnd = $latest?->EndDate;
        do {
            $chain[] = $next = PeriodScheduleHelper::proposeNext($space->Frequency, $space->CustomDays, $lastEnd, $date);
            $lastEnd = $next['end'];
        } while ($next['end'] < $date && count($chain) < self::MAX_CREATED_PERIODS);
        if ($next['end'] < $date || $next['start'] > $date) {
            return 'Creá primero el periodo en '.$space->Name;
        }

        return $this->createChain($space, $chain);
    }

    /**
     * Create the periods that step back from the first period of the space until one contains the date (at most 12).
     *
     * @param  SpaceModel  $space  The destination space.
     * @param  string  $firstStart  The start date of the space's first period.
     * @param  string  $date  The date 'Y-m-d', before $firstStart.
     * @return PeriodModel|string The period that contains the date, or the 409 message when 12 are not enough.
     */
    private function createBackward(SpaceModel $space, string $firstStart, string $date): PeriodModel|string
    {
        $chain = [];
        do {
            array_unshift($chain, $previous = PeriodScheduleHelper::proposePrevious($space->Frequency, $space->CustomDays, $firstStart));
            $firstStart = $previous['start'];
        } while ($previous['start'] > $date && count($chain) < self::MAX_CREATED_PERIODS);
        if ($previous['start'] > $date) {
            return 'La fecha '.date('d/m/Y', strtotime($date)).' es anterior al primer periodo de '.$space->Name.'; creá o adelantá su periodo';
        }

        $this->createChain($space, $chain);

        return $this->periodQueryRepository->findOverlapping($space->IdSpace, $date, $date);
    }

    /**
     * Create the given periods in order and return the last one.
     *
     * @param  SpaceModel  $space  The space.
     * @param  list<array{start:string,end:string}>  $chain  The period dates.
     * @return PeriodModel The last created period.
     */
    private function createChain(SpaceModel $space, array $chain): PeriodModel
    {
        foreach ($chain as $dates) {
            $created = $this->periodService->createWithinTransaction($space, $dates['start'], $dates['end']);
        }

        return $this->periodQueryRepository->find($created->get('period')['id']);
    }
}
