<?php

namespace App\Repositories\Period;

use App\Helpers\MoneyHelper;
use App\Models\Period\PeriodModel;
use Illuminate\Support\Collection;
use App\Models\Installment\CardModel;
use App\Models\Template\TemplateModel;
use App\Repositories\BaseQueryRepository;
use App\Models\Installment\InstallmentModel;

class PeriodQueryRepository extends BaseQueryRepository
{
    /**
     * Find a period by identifier, optionally locking its row for the current transaction.
     *
     * @param  int  $periodId  The period identifier.
     * @param  bool  $lock  Whether to lock the row (FOR UPDATE).
     * @return PeriodModel|null The period, or null when not found.
     */
    public function find(int $periodId, bool $lock = false): ?PeriodModel
    {
        $query = PeriodModel::query();

        return ($lock ? $query->lockForUpdate() : $query)->find($periodId);
    }

    /**
     * The most recent period, optionally locking its row.
     *
     * @param  bool  $lock  Whether to lock the row (FOR UPDATE).
     * @return PeriodModel|null The latest period, or null when there are none.
     */
    public function latest(bool $lock = false): ?PeriodModel
    {
        $query = PeriodModel::query()->orderByDesc('year')->orderByDesc('month')->orderByDesc('half');

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    /**
     * Every period in chronological order.
     *
     * @return Collection<int, PeriodModel> The periods.
     */
    public function listChronological(): Collection
    {
        return PeriodModel::query()->orderBy('year')->orderBy('month')->orderBy('half')->get();
    }

    /**
     * Find a period by its year, month and half key.
     *
     * @param  array{year:int,month:int,half:int}  $key  The period key.
     * @return PeriodModel|null The period, or null when not found.
     */
    public function findByKey(array $key): ?PeriodModel
    {
        return PeriodModel::query()->where($key)->first();
    }

    /**
     * Every card.
     *
     * @return Collection<int, CardModel> The cards.
     */
    public function listCards(): Collection
    {
        return CardModel::query()->orderBy('id')->get();
    }

    /**
     * Installments mapped to the calculators' camelCase input, with money in cents.
     *
     * @return Collection<int, array<string, mixed>> The installments.
     */
    public function listInstallmentInputs(): Collection
    {
        return InstallmentModel::query()->orderBy('id')->get()->map(fn (InstallmentModel $i) => [
            'id' => $i->id,
            'cardId' => $i->card_id,
            'type' => $i->type,
            'totalInstallments' => $i->total_installments,
            'paidInstallments' => $i->paid_installments,
            'lastAdvancedMonth' => $i->last_advanced_month,
            'initialBalance' => (int) MoneyHelper::toCents($i->initial_balance),
            'installmentAmount' => MoneyHelper::toCents($i->installment_amount),
            'balanceOverride' => MoneyHelper::toCents($i->balance_override),
        ]);
    }

    /**
     * Active templates mapped to the TemplateExpander input, with the amount in cents.
     *
     * @return Collection<int, array<string, mixed>> The templates.
     */
    public function listActiveTemplateInputs(): Collection
    {
        return TemplateModel::query()->where('active', true)->orderBy('id')->get()->map(fn (TemplateModel $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'category' => $t->category,
            'estimated' => (int) MoneyHelper::toCents($t->estimated),
            'frequency' => $t->frequency,
            'half' => $t->half,
            'active' => true,
        ]);
    }
}
