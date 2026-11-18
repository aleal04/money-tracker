<?php

namespace App\Contracts\Services\Dashboard;

interface DashboardServiceContract
{
    /**
     * Build the home page data for a space (primary when none is given).
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return array{current: ?array, cards: array, templateCount: int} The latest period detail (null when none), the card views and the template count.
     */
    public function data(?int $spaceId = null): array;
}
