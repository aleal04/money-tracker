<?php

namespace App\Services\Period;

use Closure;
use Illuminate\Support\Arr;
use App\Helpers\MoneyHelper;
use App\Helpers\PeriodKeyHelper;
use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
use App\Models\Period\PeriodModel;
use Illuminate\Support\Collection;
use App\DTOs\Period\PeriodStoreDto;
use App\Constant\LineSourceConstant;
use App\DTOs\Period\PeriodUpdateDto;
use App\Helpers\PeriodScheduleHelper;
use App\Repositories\Period\LineRepository;
use App\Services\Template\TemplateExpander;
use App\Repositories\Period\PeriodRepository;
use App\Repositories\Period\LineQueryRepository;
use App\Repositories\Space\SpaceQueryRepository;
use App\Repositories\Period\PeriodQueryRepository;
use App\Contracts\Services\Period\PeriodServiceContract;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            $overlap = $space === null ? null : $this->periodQueryRepository->findOverlapping($space->IdSpace, $periodData->startDate, $periodData->endDate);
            $result = match (true) {
                $space === null => createValidationErrorResponse('No se encontró el espacio.', ['statusCode' => 404]),
                $overlap !== null && ($overlap->StartDate !== $periodData->startDate || $overlap->EndDate !== $periodData->endDate) => $this->overlapError(),
                default => $this->createWithinTransaction($space, $periodData->startDate, $periodData->endDate),
            };
            $stop($result);

            return $result;
        }, 'No se pudo crear el periodo.');
    }

    /**
     * Change the dates of a period in one transaction: no overlap with other live periods of its space and the period
     * keeps its place in the order. Lines already generated are not regenerated.
     *
     * @param  int  $periodId  The period identifier.
     * @param  PeriodUpdateDto  $periodData  The new dates.
     * @return object The response with the period view, or a 404/409/422 error.
     */
    public function updatePeriod(int $periodId, PeriodUpdateDto $periodData): object
    {
        return $this->periodRepository->transaction(function (Closure $stop) use ($periodId, $periodData): object {
            $period = $this->periodQueryRepository->find($periodId, true);
            $overlap = $period === null ? null : $this->periodQueryRepository->findOverlapping($period->FkSpace, $periodData->startDate, $periodData->endDate, $periodId);
            $previous = $period === null ? null : $this->periodQueryRepository->previousOf($period);
            $next = $period === null ? null : $this->periodQueryRepository->nextOf($period);
            $result = match (true) {
                $period === null => createValidationErrorResponse('No se encontró el periodo.', ['statusCode' => 404]),
                $overlap !== null => $this->overlapError(),
                ($previous !== null && $periodData->startDate <= $previous->StartDate) || ($next !== null && $periodData->startDate >= $next->StartDate) => createValidationErrorResponse('El cambio de fechas cambiaría el orden de los periodos', ['statusCode' => 409]),
                default => null,
            };
            if ($result !== null) {
                $stop($result);
            }
            $this->periodRepository->updateDates($period, $periodData->startDate, $periodData->endDate);

            return createValidationSuccessResponse('Periodo actualizado.', ['period' => $period->refresh()->toView()]);
        }, 'No se pudo actualizar el periodo.');
    }

    /**
     * The dates and label the space's frequency proposes for its next period (primary space when none is given).
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return array{startDate:string,endDate:string,label:string} The proposal.
     */
    public function nextProposal(?int $spaceId = null): array
    {
        $space = $this->requireSpace($spaceId);
        $next = PeriodScheduleHelper::proposeNext($space->Frequency, $space->CustomDays, $this->periodQueryRepository->latest($space->IdSpace)?->EndDate, now()->toDateString());

        return ['startDate' => $next['start'], 'endDate' => $next['end'], 'label' => PeriodScheduleHelper::label($space->Frequency, $next['start'], $next['end'])];
    }

    /**
     * The 422 response of the overlap rule.
     *
     * @return object The error response keyed on startDate.
     */
    private function overlapError(): object
    {
        $message = 'Las fechas se traslapan con otro periodo';

        return createValidationErrorResponse($message, ['statusCode' => 422, 'errors' => ['startDate' => [$message]]]);
    }

    /**
     * The given space (primary when none is given); a missing or deleted space is a 404.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return SpaceModel The space.
     */
    private function requireSpace(?int $spaceId): SpaceModel
    {
        return $this->spaceQueryRepository->resolve($spaceId) ?? throw new NotFoundHttpException('No se encontró el espacio.');
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
                $latest === null => createValidationErrorResponse('No hay periodos para continuar.', ['statusCode' => 409]),
                default => $this->createAfter($latest),
            };
            $stop($result);

            return $result;
        }, 'No se pudo crear el periodo.');
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
            $targets = $templates->pluck('targetSpaceId', 'id');
            foreach (TemplateExpander::expandForPeriod($templates, $period->monthSlot()) as $line) {
                $this->lineRepository->insert([
                    'FkPeriod' => $period->IdPeriod,
                    'Category' => $line['category'],
                    'Name' => $line['name'],
                    'Estimated' => MoneyHelper::fromCents($line['estimated']),
                    'Actual' => null,
                    'Source' => LineSourceConstant::TEMPLATE,
                    'FkTemplate' => $line['templateId'],
                    'FkTargetSpace' => $targets[$line['templateId']] ?? null,
                ]);
            }
            $this->cuotasSyncService->syncWithinTransaction($period->IdPeriod);
        }

        return createValidationSuccessResponse('Periodo listo.', ['period' => $period->toView(), 'created' => $created]);
    }

    /**
     * The id of the latest period of a space (primary when none is given), or null when there are none.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return int|null The latest period id; a missing space is a 404.
     */
    public function latestPeriodId(?int $spaceId = null): ?int
    {
        return $this->periodQueryRepository->latest($this->requireSpace($spaceId)->IdSpace)?->IdPeriod;
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
        $summary = PeriodSummaryCalculator::summarize($lines);
        $balance = $this->balanceOf($period, $ids->slice(0, $index + 1));

        return [
            'period' => $period->toView(),
            'prevId' => $ids[$index - 1] ?? null,
            'nextId' => $ids[$index + 1] ?? null,
            'lines' => $lines,
            'summary' => $summary + ['saldoAnterior' => $balance['saldoAnterior'], 'restante' => $balance['restante'], 'entro' => $summary['ingreso'], 'gastado' => $summary['egreso']],
        ];
    }

    /**
     * The latest period of a space with its live remaining balance, for the home summary.
     *
     * @param  int  $spaceId  The space identifier.
     * @return array{periodId:int,label:string,restante:int}|null The current period, or null when the space has none.
     */
    public function currentOf(int $spaceId): ?array
    {
        $period = $this->periodQueryRepository->latest($spaceId);
        if ($period === null) {
            return null;
        }
        $ids = $this->periodQueryRepository->listChronological($spaceId)->pluck('IdPeriod')->values();

        return ['periodId' => $period->IdPeriod, 'label' => $period->label(), 'restante' => $this->balanceOf($period, $ids)['restante']];
    }

    /**
     * Saldo anterior and restante of a period: the chain over the periods of its space up to it, live (open periods
     * count, carried lines do not), with a single grouped lines query.
     *
     * @param  PeriodModel  $period  The period.
     * @param  Collection<int, int>  $ids  The ids of the space's periods by start date, ending with this one.
     * @return array{saldoAnterior:int,restante:int} The balance in cents.
     */
    private function balanceOf(PeriodModel $period, Collection $ids): array
    {
        $totals = $this->lineQueryRepository->totalsBySpaceUpTo($period->FkSpace, $period->StartDate);
        $summaries = $ids->map(fn (int $id) => $totals[$id] ?? (object) ['ingreso' => 0, 'egreso' => 0]);

        return Arr::last(BalanceCalculator::chain(MoneyHelper::requireCents($period->space->InitialBalance), $summaries));
    }
}
