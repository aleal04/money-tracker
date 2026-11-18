<?php

namespace App\Repositories\Period;

use Closure;
use App\Models\Period\PeriodModel;
use Illuminate\Support\Facades\DB;
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
     * Insert the period if its key is absent (atomic on the unique key, no nested transaction); to run inside a transaction.
     *
     * @param  array{year:int,month:int,half:int}  $key  The period key.
     * @return array{period: PeriodModel, created: bool} The period and whether this call created it.
     */
    public function insertIfAbsent(array $key): array
    {
        $created = PeriodModel::query()->insertOrIgnore([...$key, 'closed' => false, 'created_at' => now(), 'updated_at' => now()]) === 1;

        return ['period' => PeriodModel::query()->where($key)->lockForUpdate()->firstOrFail(), 'created' => $created];
    }

    /**
     * Set the closed flag of a period; to run inside a transaction.
     *
     * @param  PeriodModel  $period  The locked period.
     * @param  bool  $closed  The new flag.
     */
    public function setClosed(PeriodModel $period, bool $closed): void
    {
        $period->update(['closed' => $closed]);
    }

    /**
     * Advance the paid cuotas of every installment up to the target month; to run inside a transaction.
     *
     * @param  string  $targetMonth  The month key "YYYY-MM" to advance to.
     */
    public function advanceInstallments(string $targetMonth): void
    {
        foreach (InstallmentModel::query()->get() as $installment) {
            $next = InstallmentCalculator::advance($installment->paid_installments, $installment->total_installments, $installment->last_advanced_month, $targetMonth);

            if ($next['paid'] !== $installment->paid_installments || $next['lastAdvanced'] !== $installment->last_advanced_month) {
                $installment->update(['paid_installments' => $next['paid'], 'last_advanced_month' => $next['lastAdvanced']]);
            }
        }
    }
}
