<?php

namespace Tests\Feature\Period;

class BalanceTest extends PeriodTestCase
{
    /**
     * Add a line to a period.
     *
     * @param  int  $periodId  The period identifier.
     * @param  string  $category  The category.
     * @param  string  $amount  The estimated amount as typed.
     * @return int The line identifier.
     */
    private function line(int $periodId, string $category, string $amount): int
    {
        return $this->postJson("/periods/{$periodId}/lines/store", ['category' => $category, 'name' => 'x', 'estimated' => $amount])->assertCreated()->json('line.id');
    }

    /**
     * The balance of a period as [saldoAnterior, entro, gastado, restante].
     *
     * @param  int  $periodId  The period identifier.
     * @return array<int, int> The balance.
     */
    private function balance(int $periodId): array
    {
        $s = $this->getJson("/periods/{$periodId}/data")->assertOk()->json('summary');

        return [$s['saldoAnterior'], $s['entro'], $s['gastado'], $s['restante']];
    }

    /**
     * The chain over three open periods: positive, then negative; each saldo anterior is the previous restante.
     */
    public function test_chain_over_three_periods_with_negative(): void
    {
        $a = $this->makePeriod($this->dates(11, 1))['id'];
        $b = $this->makePeriod($this->dates(11, 2))['id'];
        $c = $this->makePeriod($this->dates(12, 1))['id'];
        $this->line($a, 'ingreso', '1.000');
        $this->line($a, 'gasto', '300');
        $this->line($b, 'gasto', '900');
        $this->line($c, 'ingreso', '50');

        $this->assertSame([0, 100000, 30000, 70000], $this->balance($a));
        $this->assertSame([70000, 0, 90000, -20000], $this->balance($b));
        $this->assertSame([-20000, 5000, 0, -15000], $this->balance($c));
    }

    /**
     * The arrastre is not counted twice: the carried line leaves the closed period and counts in the next one.
     */
    public function test_arrastre_is_not_double_counted(): void
    {
        $a = $this->makePeriod($this->dates(11, 1))['id'];
        $this->line($a, 'ingreso', '1.000');
        $this->line($a, 'gasto', '300');
        $this->assertSame(70000, $this->balance($a)[3]);

        $this->postJson("/periods/{$a}/close")->assertCreated();
        $b = $this->period(11, 2)->IdPeriod;

        $this->assertSame([0, 100000, 0, 100000], $this->balance($a));
        $this->assertSame([100000, 0, 30000, 70000], $this->balance($b));
    }

    /**
     * Editing a line of an old period changes the saldo anterior of the later ones.
     */
    public function test_editing_an_old_period_propagates(): void
    {
        $a = $this->makePeriod($this->dates(11, 1))['id'];
        $b = $this->makePeriod($this->dates(11, 2))['id'];
        $line = $this->line($a, 'ingreso', '1.000');
        $this->assertSame(100000, $this->balance($b)[0]);

        $this->putJson("/lines/{$line}/update", ['estimated' => '1.500'])->assertOk();
        $this->assertSame([150000, 0, 0, 150000], $this->balance($b));
        $this->deleteJson("/lines/{$line}/delete")->assertOk();
        $this->assertSame(0, $this->balance($b)[0]);
    }

    /**
     * The initial balance of the space applies to its first period only; spaces do not mix.
     */
    public function test_initial_balance_applies_to_the_first_period_of_its_space(): void
    {
        $space = $this->postJson('/spaces/store', ['name' => 'Ahorros', 'frequency' => 'monthly', 'initialBalance' => '2.000,50'])->assertCreated()->json('space.id');
        $first = $this->postJson('/periods/store', ['spaceId' => $space, 'startDate' => '2026-11-01', 'endDate' => '2026-11-30'])->assertCreated()->json('period.id');
        $second = $this->postJson('/periods/store', ['spaceId' => $space, 'startDate' => '2026-12-01', 'endDate' => '2026-12-31'])->assertCreated()->json('period.id');
        $primary = $this->makePeriod($this->dates(11, 1))['id'];
        $this->line($primary, 'ingreso', '999');
        $this->line($first, 'gasto', '500');

        $this->assertSame([200050, 0, 50000, 150050], $this->balance($first));
        $this->assertSame([150050, 0, 0, 150050], $this->balance($second));
        $this->assertSame([0, 99900, 0, 99900], $this->balance($primary));
    }
}
