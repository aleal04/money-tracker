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
        $query = PeriodModel::query()->orderByDesc('Year')->orderByDesc('Month')->orderByDesc('Half');

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    /**
     * Every period in chronological order.
     *
     * @return Collection<int, PeriodModel> The periods.
     */
    public function listChronological(): Collection
    {
        return PeriodModel::query()->orderBy('Year')->orderBy('Month')->orderBy('Half')->get();
    }

    /**
     * Find a period by its year, month and half key.
     *
     * @param  array{year:int,month:int,half:int}  $key  The period key.
     * @return PeriodModel|null The period, or null when not found.
     */
    public function findByKey(array $key): ?PeriodModel
    {
        return PeriodModel::query()->where(PeriodModel::keyColumns($key))->first();
    }

    /**
     * Every card.
     *
     * @return Collection<int, CardModel> The cards.
     */
    public function listCards(): Collection
    {
        return CardModel::query()->orderBy('IdCard')->get();
    }

    /**
     * Installments mapped to the calculators' camelCase input, with money in cents.
     *
     * @return Collection<int, array<string, mixed>> The installments.
     */
    public function listInstallmentInputs(): Collection
    {
        return InstallmentModel::query()->orderBy('IdInstallment')->get()->map(fn (InstallmentModel $i) => $i->toInput());
    }

    /**
     * Active templates mapped to the TemplateExpander input, with the amount in cents.
     *
     * @return Collection<int, array<string, mixed>> The templates.
     */
    public function listActiveTemplateInputs(): Collection
    {
        return TemplateModel::query()->where('Active', true)->orderBy('IdTemplate')->get()->map(fn (TemplateModel $t) => [
            'id' => $t->IdTemplate,
            'name' => $t->Name,
            'category' => $t->Category,
            'estimated' => (int) MoneyHelper::toCents($t->Estimated),
            'frequency' => $t->Frequency,
            'half' => $t->Half,
            'active' => true,
        ]);
    }
}
