<?php

namespace App\Services\Period;

use App\Helpers\MoneyHelper;
use App\Constant\CategoryConstant;
use App\Models\Period\PeriodModel;
use App\Constant\LineSourceConstant;
use App\Repositories\Period\LineRepository;
use App\Repositories\Period\PeriodRepository;
use App\Repositories\Period\LineQueryRepository;
use App\Repositories\Period\PeriodQueryRepository;
use App\Services\Installment\InstallmentCalculator;
use App\Contracts\Services\Period\CuotasSyncServiceContract;

class CuotasSyncService implements CuotasSyncServiceContract
{
    /**
     * Create the cuotas sync service with its dependencies.
     *
     * @param  PeriodRepository  $periodRepository  The period writes and transactions.
     * @param  PeriodQueryRepository  $periodQueryRepository  The period, card and installment reads.
     * @param  LineRepository  $lineRepository  The line writes.
     * @param  LineQueryRepository  $lineQueryRepository  The line reads.
     */
    public function __construct(
        private readonly PeriodRepository $periodRepository,
        private readonly PeriodQueryRepository $periodQueryRepository,
        private readonly LineRepository $lineRepository,
        private readonly LineQueryRepository $lineQueryRepository,
    ) {}

    /**
     * Sync the "Cuotas <card>" lines in their own transaction.
     *
     * @param  int|null  $periodId  The period to sync, or null for every open period since the latest I quincena month.
     * @return object The success response, or the error response.
     */
    public function sync(?int $periodId = null): object
    {
        return $this->periodRepository->transaction(function () use ($periodId): object {
            $this->syncWithinTransaction($periodId);

            return createValidationSuccessResponse('Cuotas sincronizadas correctamente.');
        }, 'No se pudieron sincronizar las cuotas.');
    }

    /**
     * Sync the cuotas lines without opening a transaction, so it can join an outer one (period creation).
     * Inserts the line with actual=null, updates only estimated, skips paid lines and closed periods.
     *
     * @param  int|null  $periodId  The period to sync, or null for the all-open scope.
     */
    public function syncWithinTransaction(?int $periodId = null): void
    {
        $installments = $this->periodQueryRepository->listInstallmentInputs();
        $cards = $this->periodQueryRepository->listCards();

        foreach ($this->periodsToSync($periodId) as $period) {
            foreach ($cards as $card) {
                $amount = InstallmentCalculator::cuotasLineAmount($installments->where('cardId', $card->id), $period->half);
                $line = $this->lineQueryRepository->findInstallmentsLine($period->id, $card->id);

                if ($line === null) {
                    if ($amount > 0) {
                        $this->lineRepository->insert([
                            'period_id' => $period->id,
                            'category' => CategoryConstant::DEUDA_FIJA,
                            'name' => 'Cuotas '.$card->name,
                            'estimated' => MoneyHelper::fromCents($amount),
                            'actual' => null,
                            'source' => LineSourceConstant::INSTALLMENTS,
                            'card_id' => $card->id,
                        ]);
                    }
                } elseif (! $line->paid) {
                    $this->lineRepository->updateEstimated($line, MoneyHelper::fromCents($amount));
                }
            }
        }
    }

    /**
     * The open periods to sync. Installments reflect the state as of the latest I quincena; older open periods keep
     * the amounts of their own month.
     *
     * @param  int|null  $periodId  A single period, or null for the all-open scope.
     * @return iterable<PeriodModel> The open periods.
     */
    private function periodsToSync(?int $periodId): iterable
    {
        if ($periodId !== null) {
            $period = $this->periodQueryRepository->find($periodId, true);

            return $period !== null && ! $period->closed ? [$period] : [];
        }

        $periods = $this->periodQueryRepository->listChronological();
        $monthIndex = fn ($p) => $p->year * 12 + $p->month;
        $from = $periods->where('half', 1)->map($monthIndex)->max() ?? 0;

        return $periods->where('closed', false)->filter(fn ($p) => $monthIndex($p) >= $from);
    }
}
