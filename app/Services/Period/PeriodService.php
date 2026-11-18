<?php

namespace App\Services\Period;

use Closure;
use App\Helpers\MoneyHelper;
use App\Helpers\PeriodKeyHelper;
use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
use App\Models\Period\PeriodModel;
use App\DTOs\Period\PeriodStoreDto;
use App\Constant\LineSourceConstant;
use App\Helpers\PeriodScheduleHelper;
use App\Repositories\Period\LineRepository;
use App\Services\Template\TemplateExpander;
use App\Repositories\Period\PeriodRepository;
use App\Repositories\Period\LineQueryRepository;
use App\Repositories\Space\SpaceQueryRepository;
use App\Repositories\Period\PeriodQueryRepository;
use App\Contracts\Services\Period\PeriodServiceContract;

class PeriodService implements PeriodServiceContract
{
    /**
     * Create the period service with its dependencies.
     *
     * @param  PeriodRepository  $periodRepository  The period writes and transactions.
     * @param  PeriodQueryRepository  $periodQueryRepository  The period reads.
     * @param  LineRepository  $lineRepository  The line writes.
     * @param  LineQueryRepository  $lineQueryRepository  The line reads.
     * @param  CuotasSyncService  $cuotasSyncService  The cuotas lines sync.
     * @param  SpaceQueryRepository  $spaceQueryRepository  The space reads.
     */
    public function __construct(
        private readonly PeriodRepository $periodRepository,
        private readonly PeriodQueryRepository $periodQueryRepository,
        private readonly LineRepository $lineRepository,
        private readonly LineQueryRepository $lineQueryRepository,
        private readonly CuotasSyncService $cuotasSyncService,
        private readonly SpaceQueryRepository $spaceQueryRepository,
    ) {}

    /**
     * Create the period of a space (primary when none is given) in one transaction (idempotent on the start date).
     *
     * @param  PeriodStoreDto  $periodData  The space and dates.
     * @return object The response with the period view, or a 404 error when the space is not found.
     */
    public function storePeriod(PeriodStoreDto $periodData): object
    {
        return $this->periodRepository->transaction(function (Closure $stop) use ($periodData): object {
            $space = $this->spaceQueryRepository->resolve($periodData->spaceId);
            $result = $space === null
                ? createValidationErrorResponse('No se encontró el espacio.', ['statusCode' => 404])
                : $this->createWithinTransaction($space, $periodData->startDate, $periodData->endDate);
            $stop($result);

            return $result;
        }, 'No se pudo crear la quincena.');
    }

    /**
     * Create the period that follows the latest one of a space (primary when none is given), with the dates its
     * frequency proposes, in one transaction (idempotent under concurrent calls).
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return object The response with the period view, or a 404/409 error when the space is not found or has no periods.
     */
    public function storeNextPeriod(?int $spaceId = null): object
    {
        return $this->periodRepository->transaction(function (Closure $stop) use ($spaceId): object {
            $space = $this->spaceQueryRepository->resolve($spaceId);
            $latest = $space === null ? null : $this->periodQueryRepository->latest($space->IdSpace, true);
            $result = match (true) {
                $space === null => createValidationErrorResponse('No se encontró el espacio.', ['statusCode' => 404]),
                $latest === null => createValidationErrorResponse('No hay quincenas para continuar.', ['statusCode' => 409]),
                default => $this->createAfter($latest),
            };
            $stop($result);

            return $result;
        }, 'No se pudo crear la quincena.');
    }

    /**
     * Create the period its space's frequency proposes right after the given one; to run inside a transaction.
     *
     * @param  PeriodModel  $period  The period to continue from.
     * @return object The response with the period view.
     */
    public function createAfter(PeriodModel $period): object
    {
        $space = $period->space;
        $next = PeriodScheduleHelper::proposeNext($space->Frequency, $space->CustomDays, $period->EndDate, now()->toDateString());

        return $this->createWithinTransaction($space, $next['start'], $next['end']);
    }

    /**
     * Create the period without opening a transaction, so it can run inside an outer one (e.g. closing a period).
     * An existing start date returns the same period and adds nothing; the first period of a month in the space
     * advances the installments of the space's cards, then the template lines and the cuotas lines are generated.
     *
     * @param  SpaceModel  $space  The space.
     * @param  string  $startDate  The start date 'Y-m-d'.
     * @param  string  $endDate  The end date 'Y-m-d'.
     * @return object The response with the period view.
     */
    public function createWithinTransaction(SpaceModel $space, string $startDate, string $endDate): object
    {
        ['period' => $period, 'created' => $created] = $this->periodRepository->insertIfAbsent($space->IdSpace, $startDate, $endDate);

        if ($created) {
            if (! $this->periodQueryRepository->hasOtherStartingInMonth($space->IdSpace, $startDate, $period->IdPeriod)) {
                [$year, $month] = array_map('intval', explode('-', $startDate));
                $this->periodRepository->advanceInstallments($space->IdSpace, PeriodKeyHelper::prevMonthKey($year, $month));
            }
            $templates = $this->periodQueryRepository->listActiveTemplateInputs($space->IdSpace);
            foreach (TemplateExpander::expandForPeriod($templates, $period->monthSlot()) as $line) {
                $this->lineRepository->insert([
                    'FkPeriod' => $period->IdPeriod,
                    'Category' => $line['category'],
                    'Name' => $line['name'],
                    'Estimated' => MoneyHelper::fromCents($line['estimated']),
                    'Actual' => null,
                    'Source' => LineSourceConstant::TEMPLATE,
                    'FkTemplate' => $line['templateId'],
                ]);
            }
            $this->cuotasSyncService->syncWithinTransaction($period->IdPeriod);
        }

        return createValidationSuccessResponse('Quincena lista.', ['period' => $period->toView(), 'created' => $created]);
    }

    /**
     * The id of the latest period of a space (primary when none is given), or null when there are none.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return int|null The latest period id.
     */
    public function latestPeriodId(?int $spaceId = null): ?int
    {
        $space = $this->spaceQueryRepository->resolve($spaceId);

        return $space === null ? null : $this->periodQueryRepository->latest($space->IdSpace)?->IdPeriod;
    }

    /**
     * Whether a period exists.
     *
     * @param  int  $periodId  The period identifier.
     * @return bool True when found.
     */
    public function exists(int $periodId): bool
    {
        return $this->periodQueryRepository->find($periodId) !== null;
    }

    /**
     * A period with its neighbours, lines (with the carried flag) and summary.
     *
     * @param  int  $periodId  The period identifier.
     * @return array{period: array<string, mixed>, prevId: ?int, nextId: ?int, lines: array<int, array<string, mixed>>, summary: array<string, mixed>}|null The detail, or null when not found.
     */
    public function detail(int $periodId): ?array
    {
        $period = $this->periodQueryRepository->find($periodId);
        if ($period === null) {
            return null;
        }

        $ids = $this->periodQueryRepository->listChronological($period->FkSpace)->pluck('IdPeriod')->values();
        $index = $ids->search($periodId);
        $lines = $this->lineQueryRepository->listByPeriod($periodId)->map(fn (LineModel $line) => $line->toView())->all();

        return [
            'period' => $period->toView(),
            'prevId' => $ids[$index - 1] ?? null,
            'nextId' => $ids[$index + 1] ?? null,
            'lines' => $lines,
            'summary' => PeriodSummaryCalculator::summarize($lines),
        ];
    }
}
