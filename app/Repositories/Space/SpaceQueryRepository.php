<?php

namespace App\Repositories\Space;

use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
use App\Models\Period\PeriodModel;
use Illuminate\Support\Collection;
use App\Models\Installment\CardModel;
use App\Models\Template\TemplateModel;
use App\Repositories\BaseQueryRepository;

class SpaceQueryRepository extends BaseQueryRepository
{
    /**
     * The given space, or the primary space when no id is given.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return SpaceModel|null The space, or null when not found.
     */
    public function resolve(?int $spaceId): ?SpaceModel
    {
        return $spaceId === null ? SpaceModel::query()->where('IsPrimary', true)->first() : SpaceModel::query()->find($spaceId);
    }

    /**
     * Find a space by identifier, optionally locking its row for the current transaction.
     *
     * @param  int  $spaceId  The space identifier.
     * @param  bool  $lock  Whether to lock the row (FOR UPDATE).
     * @return SpaceModel|null The space, or null when not found.
     */
    public function findSpace(int $spaceId, bool $lock = false): ?SpaceModel
    {
        $query = SpaceModel::query();

        return ($lock ? $query->lockForUpdate() : $query)->find($spaceId);
    }

    /**
     * Every space in creation order, with whether it has live periods (periods_exists).
     *
     * @return Collection<int, SpaceModel> The spaces.
     */
    public function listSpaces(): Collection
    {
        return SpaceModel::query()->withExists('periods')->orderBy('IdSpace')->get();
    }

    /**
     * Whether there is any live space marked as primary.
     *
     * @return bool True when a primary space exists.
     */
    public function hasPrimary(): bool
    {
        return SpaceModel::query()->where('IsPrimary', true)->exists();
    }

    /**
     * Whether the space has any live period.
     *
     * @param  int  $spaceId  The space identifier.
     * @return bool True when it has periods.
     */
    public function hasPeriods(int $spaceId): bool
    {
        return PeriodModel::query()->where('FkSpace', $spaceId)->exists();
    }

    /**
     * Whether anything depends on the space: periods, cards, templates, or lines and templates that target it.
     *
     * @param  int  $spaceId  The space identifier.
     * @return bool True when the space has data.
     */
    public function hasData(int $spaceId): bool
    {
        return PeriodModel::query()->where('FkSpace', $spaceId)->exists()
            || CardModel::query()->where('FkSpace', $spaceId)->exists()
            || TemplateModel::query()->where(fn ($q) => $q->where('FkSpace', $spaceId)->orWhere('FkTargetSpace', $spaceId))->exists()
            || LineModel::query()->where('FkTargetSpace', $spaceId)->exists();
    }
}
