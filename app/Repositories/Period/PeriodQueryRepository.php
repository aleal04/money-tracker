<?php

namespace App\Repositories\Period;

use Carbon\CarbonImmutable;
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
     * The most recent period of a space (by start date), optionally locking its row.
     *
     * @param  int  $spaceId  The space identifier.
     * @param  bool  $lock  Whether to lock the row (FOR UPDATE).
     * @return PeriodModel|null The latest period, or null when the space has none.
     */
    public function latest(int $spaceId, bool $lock = false): ?PeriodModel
    {
        $query = PeriodModel::query()->where('FkSpace', $spaceId)->orderByDesc('StartDate');

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    /**
     * Periods in chronological order (by space, then start date), of one space or of every space.
     *
     * @param  int|null  $spaceId  The space identifier, or null for every space.
     * @return Collection<int, PeriodModel> The periods.
     */
    public function listChronological(?int $spaceId = null): Collection
    {
        return PeriodModel::query()->when($spaceId !== null, fn ($q) => $q->where('FkSpace', $spaceId))->orderBy('FkSpace')->orderBy('StartDate')->get();
    }

    /**
     * The period of the same space that starts right after the given one.
     *
     * @param  PeriodModel  $period  The period.
     * @return PeriodModel|null The next period, or null when there is none.
     */
    public function nextOf(PeriodModel $period): ?PeriodModel
    {
        return PeriodModel::query()->where('FkSpace', $period->FkSpace)->where('StartDate', '>', $period->StartDate)->orderBy('StartDate')->first();
    }

    /**
     * The period of the same space that starts right before the given one.
     *
     * @param  PeriodModel  $period  The period.
     * @return PeriodModel|null The previous period, or null when there is none.
     */
    public function previousOf(PeriodModel $period): ?PeriodModel
    {
        return PeriodModel::query()->where('FkSpace', $period->FkSpace)->where('StartDate', '<', $period->StartDate)->orderByDesc('StartDate')->first();
    }

    /**
     * A live period of the space whose dates overlap [startDate, endDate] (both ends inclusive).
     *
     * @param  int  $spaceId  The space identifier.
     * @param  string  $startDate  The start date 'Y-m-d'.
     * @param  string  $endDate  The end date 'Y-m-d'.
     * @param  int  $exceptPeriodId  The period to ignore (0 for none).
     * @return PeriodModel|null An overlapping period, or null when there is none.
     */
    public function findOverlapping(int $spaceId, string $startDate, string $endDate, int $exceptPeriodId = 0): ?PeriodModel
    {
        return PeriodModel::query()->where('FkSpace', $spaceId)->where('IdPeriod', '<>', $exceptPeriodId)
            ->where('StartDate', '<=', $endDate)->where('EndDate', '>=', $startDate)->orderBy('StartDate')->first();
    }

    /**
     * Whether another period of the space starts in the same month as $startDate.
     *
     * @param  int  $spaceId  The space identifier.
     * @param  string  $startDate  A start date 'Y-m-d'.
     * @param  int  $exceptPeriodId  The period to ignore.
     * @return bool True when another period starts in that month.
     */
    public function hasOtherStartingInMonth(int $spaceId, string $startDate, int $exceptPeriodId): bool
    {
        $first = CarbonImmutable::parse($startDate)->startOfMonth();

        return PeriodModel::query()->where('FkSpace', $spaceId)->where('IdPeriod', '<>', $exceptPeriodId)
            ->where('StartDate', '>=', $first->toDateString())->where('StartDate', '<', $first->addMonth()->toDateString())->exists();
    }

    /**
     * The cards of a space.
     *
     * @param  int  $spaceId  The space identifier.
     * @return Collection<int, CardModel> The cards.
     */
    public function listCards(int $spaceId): Collection
    {
        return CardModel::query()->where('FkSpace', $spaceId)->orderBy('IdCard')->get();
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
     * Active templates of a space mapped to the TemplateExpander input, with the amount in cents.
     *
     * @param  int  $spaceId  The space identifier.
     * @return Collection<int, array<string, mixed>> The templates.
     */
    public function listActiveTemplateInputs(int $spaceId): Collection
    {
        return TemplateModel::query()->where(['FkSpace' => $spaceId, 'Active' => true])->orderBy('IdTemplate')->get()->map(fn (TemplateModel $t) => [
            'id' => $t->IdTemplate,
            'name' => $t->Name,
            'category' => $t->Category,
            'estimated' => (int) MoneyHelper::toCents($t->Estimated),
            'frequency' => $t->Frequency,
            'dayOfMonth' => $t->DayOfMonth,
            'active' => true,
        ]);
    }
}
