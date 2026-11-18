<?php

namespace App\Services\Dashboard;

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
     */
    public function __construct(
        private readonly PeriodService $periodService,
        private readonly InstallmentService $installmentService,
        private readonly TemplateService $templateService,
    ) {}

    /**
     * Build the home page data.
     *
     * @return array{current: ?array, cards: array, templateCount: int} The latest period detail (null when none), the card views and the template count.
     */
    public function data(): array
    {
        $latestId = $this->periodService->latestPeriodId();

        return [
            'current' => $latestId === null ? null : $this->periodService->detail($latestId),
            'cards' => $this->installmentService->cardList(),
            'templateCount' => count($this->templateService->templateList()),
        ];
    }
}
