<?php

namespace App\Repositories\Space;

use App\Models\Space\SpaceModel;
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
}
