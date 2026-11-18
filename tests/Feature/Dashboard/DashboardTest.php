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
        $this->getJson('/dashboard/data')->assertOk()->assertExactJson([
            'current' => null, 'cards' => [], 'templateCount' => 0,
            'spaces' => [['id' => $this->primarySpaceId(), 'name' => 'Salario', 'frequency' => 'biweekly', 'isPrimary' => true, 'current' => null]],
        ]);
    }

    /**
     * With data it returns the latest period detail, the cards and the template count.
     */
    public function test_data_with_period_cards_and_templates(): void
    {
        $this->seedTemplates();
        $this->seedReloj();
        $this->makePeriod($this->dates(10, 2));
        $latest = $this->makePeriod($this->dates(11, 1));

        $this->getJson('/dashboard/data')->assertOk()
            ->assertJsonPath('current.period.id', $latest['id'])
            ->assertJsonPath('current.period.label', 'I Quincena Noviembre 2026')
            ->assertJsonPath('current.prevId', $latest['id'] - 1)
            ->assertJsonStructure(['current' => ['lines', 'summary'], 'cards' => [['id', 'name', 'installments', 'totals' => ['cuotaMensual', 'saldoFaltante', 'porQuincena']]]])
            ->assertJsonPath('templateCount', 3)
            ->assertJsonCount(1, 'cards');
    }

    /**
     * The spaces payload carries each space's latest period with its live remaining balance; ?space= scopes cards and templates.
     */
    public function test_spaces_payload_and_space_param(): void
    {
        $this->seedReloj();
        $savings = $this->postJson('/spaces/store', ['name' => 'Ahorros', 'frequency' => 'monthly', 'initialBalance' => '100'])->assertCreated()->json('space');
        $period = $this->makePeriod($this->dates(11, 1));
        $this->postJson("/periods/{$period['id']}/lines/store", ['category' => 'ingreso', 'name' => 'Pago', 'estimated' => '1.000'])->assertCreated();
        $savingsPeriod = $this->postJson('/periods/store', ['spaceId' => $savings['id'], 'startDate' => '2026-11-01', 'endDate' => '2026-11-30'])->assertCreated()->json('period');

        $spaces = $this->getJson('/dashboard/data')->assertOk()->assertJsonCount(1, 'cards')->json('spaces');
        $this->assertSame(['periodId' => $period['id'], 'label' => 'I Quincena Noviembre 2026', 'restante' => 100000 - 552131], $spaces[0]['current']);
        $this->assertSame(['periodId' => $savingsPeriod['id'], 'label' => 'Noviembre 2026', 'restante' => 10000], $spaces[1]['current']);
        $this->assertSame([true, false], array_column($spaces, 'isPrimary'));

        $this->getJson("/dashboard/data?space={$savings['id']}")->assertOk()->assertJsonCount(0, 'cards')->assertJsonPath('current.period.id', $savingsPeriod['id']);
        $this->getJson('/dashboard/data?space=9999')->assertNotFound();
    }
}
