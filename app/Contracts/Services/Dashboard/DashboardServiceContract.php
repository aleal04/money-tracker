<?php

namespace App\Contracts\Services\Dashboard;

interface DashboardServiceContract
{
    /**
     * Build the home page data.
     *
     * @return array{current: ?array, cards: array, templateCount: int} The latest period detail (null when none), the card views and the template count.
     */
    public function data(): array;
}
