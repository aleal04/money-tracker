<?php

namespace Tests\Feature\Dashboard;

use Tests\Feature\Period\PeriodTestCase;

class DashboardTest extends PeriodTestCase
{
    /**
     * The home page renders.
     */
    public function test_index_page(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('Dashboard/Index', false));
    }

    /**
     * An empty database yields no period, no cards and no templates.
     */
    public function test_data_when_empty(): void
    {
        $this->getJson('/dashboard/data')->assertOk()->assertExactJson(['current' => null, 'cards' => [], 'templateCount' => 0]);
    }

    /**
     * With data it returns the latest period detail, the cards and the template count.
     */
    public function test_data_with_period_cards_and_templates(): void
    {
        $this->seedTemplates();
        $this->seedReloj();
        $this->makePeriod(['year' => 2026, 'month' => 10, 'half' => 2]);
        $latest = $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 1]);

        $this->getJson('/dashboard/data')->assertOk()
            ->assertJsonPath('current.period.id', $latest['id'])
            ->assertJsonPath('current.period.label', 'I Quincena Noviembre')
            ->assertJsonPath('current.prevId', $latest['id'] - 1)
            ->assertJsonStructure(['current' => ['lines', 'summary'], 'cards' => [['id', 'name', 'installments', 'totals' => ['cuotaMensual', 'saldoFaltante', 'porQuincena']]]])
            ->assertJsonPath('templateCount', 3)
            ->assertJsonCount(1, 'cards');
    }
}
