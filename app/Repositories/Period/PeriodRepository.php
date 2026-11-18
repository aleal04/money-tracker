<?php

namespace App\Repositories\Period;

use Closure;
use App\Models\Period\PeriodModel;
use Illuminate\Support\Facades\DB;
use App\Models\Installment\CardModel;
use App\Models\Installment\InstallmentModel;
use App\Services\Installment\InstallmentCalculator;
use App\Repositories\Concerns\RunsTransactionStepsTrait;

class PeriodRepository
{
    use RunsTransactionStepsTrait;

    /**
     * Run steps in one transaction; used by services that compose several writes (create, close).
     *
     * @param  Closure(Closure(object): void): object  $steps  The steps; they receive the step checker and return the success response.
     * @param  string  $failureMessage  The message when an unexpected error stops the transaction.
     * @return object The success response, the failed step response or the generic error.
     */
    public function transaction(Closure $steps, string $failureMessage): object
    {
        return $this->runTransactionSteps(DB::connection(), $steps, $failureMessage, ['statusCode' => 500]);
    }

    /**
     * Insert the period if no live period of the space starts on that date (atomic on UQ_Period_FkSpace_ActiveStart,
     * no nested transaction); to run inside a transaction.
     *
     * @param  int  $spaceId  The space identifier.
     * @param  string  $startDate  The start date 'Y-m-d'.
     * @param  string  $endDate  The end date 'Y-m-d' (ignored when the period already exists).
     * @return array{period: PeriodModel, created: bool} The period and whether this call created it.
     */
    public function insertIfAbsent(int $spaceId, string $startDate, string $endDate): array
    {
        $created = PeriodModel::query()->insertOrIgnore([
            'FkSpace' => $spaceId, 'StartDate' => $startDate, 'EndDate' => $endDate, 'Closed' => false, 'CreatedAt' => now(), 'UpdatedAt' => now(),
        ]) === 1;

        return ['period' => PeriodModel::query()->where(['FkSpace' => $spaceId, 'StartDate' => $startDate])->lockForUpdate()->firstOrFail(), 'created' => $created];
    }

    /**
     * Set the closed flag of a period; to run inside a transaction.
     *
     * @param  PeriodModel  $period  The locked period.
     * @param  bool  $closed  The new flag.
     */
    public function setClosed(PeriodModel $period, bool $closed): void
    {
        $period->update(['Closed' => $closed]);
    }

    /**
     * Change the dates of a period; to run inside a transaction.
     *
     * @param  PeriodModel  $period  The locked period.
     * @param  string  $startDate  The start date 'Y-m-d'.
     * @param  string  $endDate  The end date 'Y-m-d'.
     */
    public function updateDates(PeriodModel $period, string $startDate, string $endDate): void
    {
        $period->update(['StartDate' => $startDate, 'EndDate' => $endDate]);
    }

    /**
     * Advance the paid cuotas of the installments of the space's cards up to the target month; to run inside a transaction.
     *
     * @param  int  $spaceId  The space identifier.
     * @param  string  $targetMonth  The month key "YYYY-MM" to advance to.
     */
    public function advanceInstallments(int $spaceId, string $targetMonth): void
    {
        $cardIds = CardModel::query()->where('FkSpace', $spaceId)->pluck('IdCard');
        foreach (InstallmentModel::query()->whereIn('FkCard', $cardIds)->get() as $installment) {
            $next = InstallmentCalculator::advance($installment->PaidInstallments, $installment->TotalInstallments, $installment->LastAdvancedMonth, $targetMonth);

            if ($next['paid'] !== $installment->PaidInstallments || $next['lastAdvanced'] !== $installment->LastAdvancedMonth) {
                $installment->update(['PaidInstallments' => $next['paid'], 'LastAdvancedMonth' => $next['lastAdvanced']]);
            }
        }
    }
}
