<?php

namespace App\Services\Dashboard;

use App\Services\Space\SpaceService;
use App\Services\Period\PeriodService;
use App\Services\Template\TemplateService;
use App\Services\Installment\InstallmentService;
use App\Contracts\Services\Dashboard\DashboardServiceContract;

class DashboardService implements DashboardServiceContract
{
    /**
     * Create the dashboard service with the domain services it composes.
     *
     * @param  PeriodService  $periodService  The period service.
     * @param  InstallmentService  $installmentService  The card and installment service.
     * @param  TemplateService  $templateService  The template service.
     * @param  SpaceService  $spaceService  The space service.
     */
    public function __construct(
        private readonly PeriodService $periodService,
        private readonly InstallmentService $installmentService,
        private readonly TemplateService $templateService,
        private readonly SpaceService $spaceService,
    ) {}

    /**
     * Build the home page data for a space (primary when none is given).
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return array{current: ?array, cards: array, templateCount: int, spaces: array} The latest period detail (null when none), the card views and the template count of the space, and every space with its current period and remaining balance.
     */
    public function data(?int $spaceId = null): array
    {
        $latestId = $this->periodService->latestPeriodId($spaceId);

        return [
            'current' => $latestId === null ? null : $this->periodService->detail($latestId),
            'cards' => $this->installmentService->cardList($spaceId),
            'templateCount' => count($this->templateService->templateList($spaceId)),
            'spaces' => array_map(fn (array $space) => [
                'id' => $space['id'],
                'name' => $space['name'],
                'frequency' => $space['frequency'],
                'isPrimary' => $space['isPrimary'],
                'current' => $this->periodService->currentOf($space['id']),
            ], $this->spaceService->spaceList()),
        ];
    }
}
