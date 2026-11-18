<?php

namespace App\Services\Period;

use Closure;
use App\Helpers\MoneyHelper;
use App\Models\Period\LineModel;
use App\Constant\CategoryConstant;
use Illuminate\Support\Collection;
use App\Constant\LineSourceConstant;
use App\Repositories\Period\LineRepository;
use App\Repositories\Period\PeriodRepository;
use App\Repositories\Period\LineQueryRepository;
use App\Repositories\Period\PeriodQueryRepository;
use App\Contracts\Services\Period\PeriodCloseServiceContract;

class PeriodCloseService implements PeriodCloseServiceContract
{
    /**
     * Create the close service with its dependencies.
     *
     * @param  PeriodRepository  $periodRepository  The period writes and transactions.
     * @param  PeriodQueryRepository  $periodQueryRepository  The period reads.
     * @param  LineRepository  $lineRepository  The line writes.
     * @param  LineQueryRepository  $lineQueryRepository  The line reads.
     * @param  PeriodService  $periodService  The period creation.
     */
    public function __construct(
        private readonly PeriodRepository $periodRepository,
        private readonly PeriodQueryRepository $periodQueryRepository,
        private readonly LineRepository $lineRepository,
        private readonly LineQueryRepository $lineQueryRepository,
        private readonly PeriodService $periodService,
    ) {}

    /**
     * The unpaid egreso lines a close would carry, with their total in cents.
     *
     * @param  int  $periodId  The period identifier.
     * @return array{lines: array<int, array<string, mixed>>, total: int}|null The preview, or null when the period is not found.
     */
    public function preview(int $periodId): ?array
    {
        if ($this->periodQueryRepository->find($periodId) === null) {
            return null;
        }
        $unpaid = $this->unpaidEgresos($periodId);

        return ['lines' => $unpaid->map(fn (LineModel $line) => $line->toView())->values()->all(), 'total' => $this->total($unpaid)];
    }

    /**
     * Close the period in one transaction (idempotent), carrying its unpaid egresos into one arrastre line of the next
     * period of its space (created with the proposed dates when there is none); unpaid transfer lines are not summed,
     * each one is carried as its own '↩ <name>' line that keeps its category and target space.
     *
     * @param  int  $periodId  The period identifier.
     * @return object The response with arrastreLineId (null when nothing was carried), or a 404/409 error.
     */
    public function close(int $periodId): object
    {
        return $this->periodRepository->transaction(function (Closure $stop) use ($periodId): object {
            $period = $this->periodQueryRepository->find($periodId, true);
            if ($period === null) {
                $stop($result = createValidationErrorResponse('No se encontró el periodo.', ['statusCode' => 404]));

                return $result;
            }
            if ($period->Closed) {
                return createValidationSuccessResponse('Periodo cerrado.', ['arrastreLineId' => null]);
            }

            $unpaid = $this->unpaidEgresos($periodId, true); // lock after the period row, so line writes serialise with the close
            $next = $this->periodQueryRepository->nextOf($period);
            if ($unpaid->isNotEmpty() && $next?->Closed) {
                $stop($result = createValidationErrorResponse('El periodo siguiente está cerrado; reabrila primero', ['statusCode' => 409]));

                return $result;
            }

            $this->periodRepository->setClosed($period, true);
            if ($unpaid->isEmpty()) {
                return createValidationSuccessResponse('Periodo cerrado.', ['arrastreLineId' => null]);
            }

            if ($next === null) {
                $created = $this->periodService->createAfter($period);
                $stop($created);
            }
            $nextId = $next?->IdPeriod ?? $created->get('period')['id'];
            [$transfers, $general] = $unpaid->partition(fn (LineModel $l) => $l->FkTargetSpace !== null);
            $arrastreId = null;
            if ($general->isNotEmpty()) {
                $total = MoneyHelper::fromCents($this->total($general));
                $arrastreId = $this->lineRepository->insert([
                    'FkPeriod' => $nextId,
                    'Category' => CategoryConstant::DEUDA_FIJA,
                    'Name' => 'Arrastre '.$period->label(),
                    'Estimated' => $total,
                    'Actual' => $total,
                    'Source' => LineSourceConstant::CARRIED,
                ])->IdLine;
                $this->lineRepository->markCarried($general->pluck('IdLine')->all(), $arrastreId);
            }
            // Each unpaid transfer travels on as its own line, so paying it later still reaches its target space.
            foreach ($transfers as $line) {
                $amount = $line->Actual ?? $line->Estimated;
                $carried = $this->lineRepository->insert([
                    'FkPeriod' => $nextId,
                    'Category' => $line->Category,
                    'Name' => str_starts_with($line->Name, '↩ ') ? $line->Name : mb_substr('↩ '.$line->Name, 0, 255),
                    'Estimated' => $amount,
                    'Actual' => $amount,
                    'Source' => LineSourceConstant::CARRIED,
                    'FkTargetSpace' => $line->FkTargetSpace,
                ]);
                $this->lineRepository->markCarried([$line->IdLine], $carried->IdLine);
            }

            return createValidationSuccessResponse('Periodo cerrado.', ['arrastreLineId' => $arrastreId]);
        }, 'No se pudo cerrar el periodo.');
    }

    /**
     * Reopen the period in one transaction, deleting its unpaid arrastre and carried transfer lines and un-carrying the
     * originals; a carried line that is paid or carried onward blocks it.
     *
     * @param  int  $periodId  The period identifier.
     * @return object The success response, or a 404/409 error.
     */
    public function reopen(int $periodId): object
    {
        return $this->periodRepository->transaction(function (Closure $stop) use ($periodId): object {
            $period = $this->periodQueryRepository->find($periodId, true);
            if ($period === null) {
                $stop($result = createValidationErrorResponse('No se encontró el periodo.', ['statusCode' => 404]));

                return $result;
            }

            $carriedLines = $this->lineQueryRepository->listByPeriod($periodId, true)->pluck('FkCarriedToLine')->filter()->unique()
                ->map(fn ($id) => $this->lineQueryRepository->find((int) $id, true))->filter();
            foreach ($carriedLines as $carried) {
                $conflict = match (true) {
                    $carried->Paid => 'No se puede reabrir: el arrastre ya está pagado',
                    $carried->FkCarriedToLine !== null => 'Reabrí primero el periodo siguiente',
                    default => null,
                };
                if ($conflict !== null) {
                    $stop($result = createValidationErrorResponse($conflict, ['statusCode' => 409]));

                    return $result;
                }
            }
            foreach ($carriedLines as $carried) {
                $this->lineRepository->removeArrastre($carried);
            }

            $this->periodRepository->setClosed($period, false);

            return createValidationSuccessResponse('Periodo reabierto.');
        }, 'No se pudo reabrir el periodo.');
    }

    /**
     * Unpaid egreso lines that are not carried yet.
     *
     * @param  int  $periodId  The period identifier.
     * @param  bool  $lock  Whether to lock the lines (FOR UPDATE).
     * @return Collection<int, LineModel> The lines.
     */
    private function unpaidEgresos(int $periodId, bool $lock = false): Collection
    {
        return $this->lineQueryRepository->listByPeriod($periodId, $lock)
            ->filter(fn (LineModel $l) => $l->Category !== CategoryConstant::INGRESO && ! $l->Paid && $l->FkCarriedToLine === null)
            ->values();
    }

    /**
     * Total in cents of the lines, using actual when set, else estimated.
     *
     * @param  Collection<int, LineModel>  $lines  The lines.
     * @return int The total in cents.
     */
    private function total(Collection $lines): int
    {
        return $lines->sum(fn (LineModel $l) => MoneyHelper::requireCents($l->Actual ?? $l->Estimated));
    }
}
