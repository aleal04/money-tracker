<?php

namespace App\Services\Period;

use Closure;
use App\Helpers\MoneyHelper;
use App\Helpers\PeriodKeyHelper;
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
     * Close the period in one transaction (idempotent), carrying its unpaid egresos into one arrastre line of the next period.
     *
     * @param  int  $periodId  The period identifier.
     * @return object The response with arrastreLineId (null when nothing was carried), or a 404/409 error.
     */
    public function close(int $periodId): object
    {
        return $this->periodRepository->transaction(function (Closure $stop) use ($periodId): object {
            $period = $this->periodQueryRepository->find($periodId, true);
            if ($period === null) {
                $stop($result = createValidationErrorResponse('No se encontró la quincena.', ['statusCode' => 404]));

                return $result;
            }
            if ($period->Closed) {
                return createValidationSuccessResponse('Quincena cerrada.', ['arrastreLineId' => null]);
            }

            $unpaid = $this->unpaidEgresos($periodId, true); // lock after the period row, so line writes serialise with the close
            $nextKey = PeriodKeyHelper::next($period->Year, $period->Month, $period->Half);
            if ($unpaid->isNotEmpty() && $this->periodQueryRepository->findByKey($nextKey)?->Closed) {
                $stop($result = createValidationErrorResponse('La quincena siguiente está cerrada; reabrila primero', ['statusCode' => 409]));

                return $result;
            }

            $this->periodRepository->setClosed($period, true);
            if ($unpaid->isEmpty()) {
                return createValidationSuccessResponse('Quincena cerrada.', ['arrastreLineId' => null]);
            }

            $created = $this->periodService->createWithinTransaction($nextKey);
            $stop($created);
            $total = MoneyHelper::fromCents($this->total($unpaid));
            $arrastre = $this->lineRepository->insert([
                'FkPeriod' => $created->get('period')['id'],
                'Category' => CategoryConstant::DEUDA_FIJA,
                'Name' => 'Arrastre '.PeriodKeyHelper::label($period->Year, $period->Month, $period->Half),
                'Estimated' => $total,
                'Actual' => $total,
                'Source' => LineSourceConstant::CARRIED,
            ]);
            $this->lineRepository->markCarried($unpaid->pluck('IdLine')->all(), $arrastre->IdLine);

            return createValidationSuccessResponse('Quincena cerrada.', ['arrastreLineId' => $arrastre->IdLine]);
        }, 'No se pudo cerrar la quincena.');
    }

    /**
     * Reopen the period in one transaction, deleting its unpaid arrastre line and un-carrying the originals.
     *
     * @param  int  $periodId  The period identifier.
     * @return object The success response, or a 404/409 error.
     */
    public function reopen(int $periodId): object
    {
        return $this->periodRepository->transaction(function (Closure $stop) use ($periodId): object {
            $period = $this->periodQueryRepository->find($periodId, true);
            if ($period === null) {
                $stop($result = createValidationErrorResponse('No se encontró la quincena.', ['statusCode' => 404]));

                return $result;
            }

            $carriedTo = $this->lineQueryRepository->listByPeriod($periodId, true)->firstWhere(fn (LineModel $l) => $l->FkCarriedToLine !== null)?->FkCarriedToLine;
            if ($carriedTo !== null) {
                $arrastre = $this->lineQueryRepository->find($carriedTo, true);
                $conflict = match (true) {
                    $arrastre?->Paid => 'No se puede reabrir: el arrastre ya está pagado',
                    $arrastre?->FkCarriedToLine !== null => 'Reabrí primero la quincena siguiente',
                    default => null,
                };
                if ($conflict !== null) {
                    $stop($result = createValidationErrorResponse($conflict, ['statusCode' => 409]));

                    return $result;
                }
                if ($arrastre !== null) {
                    $this->lineRepository->removeArrastre($arrastre);
                }
            }

            $this->periodRepository->setClosed($period, false);

            return createValidationSuccessResponse('Quincena reabierta.');
        }, 'No se pudo reabrir la quincena.');
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
