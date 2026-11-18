<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Template\TemplateExpander as E;

class TemplateExpanderTest extends TestCase
{
    /**
     * Build a template with defaults.
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    private function t(array $o = []): array
    {
        return $o + ['id' => 1, 'name' => 't', 'category' => 'gasto', 'estimated' => 1000000, 'frequency' => 'cada_periodo', 'dayOfMonth' => null, 'active' => true];
    }

    /**
     * A biweekly period on calendar halves, as the TemplateExpander input.
     *
     * @return array{start:string,end:string,indexInMonth:int,partsInMonth:int}
     */
    private function half(int $half): array
    {
        return $half === 1
            ? ['start' => '2026-11-01', 'end' => '2026-11-15', 'indexInMonth' => 0, 'partsInMonth' => 2]
            : ['start' => '2026-11-16', 'end' => '2026-11-30', 'indexInMonth' => 1, 'partsInMonth' => 2];
    }

    /**
     * Cada periodo in both halves.
     */
    public function test_cada_periodo_in_both_halves(): void
    {
        $this->assertSame(1000000, E::expandForPeriod([$this->t()], $this->half(1))[0]['estimated']);
        $this->assertSame(1000000, E::expandForPeriod([$this->t()], $this->half(2))[0]['estimated']);
        $this->assertSame(['templateId' => 1, 'name' => 't', 'category' => 'gasto', 'estimated' => 1000000], E::expandForPeriod([(object) $this->t()], $this->half(1))[0]);
    }

    /**
     * Mensual repartido splits between the halves like the old ceil/floor.
     */
    public function test_mensual_repartido_splits_between_halves(): void
    {
        $t = $this->t(['frequency' => 'mensual_repartido', 'estimated' => 2335]);
        $this->assertSame(1168, E::expandForPeriod([$t], $this->half(1))[0]['estimated']);
        $this->assertSame(1167, E::expandForPeriod([$t], $this->half(2))[0]['estimated']);
    }

    /**
     * Mensual día 1 / 16 lands only in the I / II quincena.
     */
    public function test_mensual_dia_only_in_its_half(): void
    {
        $t = $this->t(['frequency' => 'mensual_dia', 'dayOfMonth' => 16]);
        $this->assertSame([], E::expandForPeriod([$t], $this->half(1)));
        $this->assertCount(1, E::expandForPeriod([$t], $this->half(2)));
        $this->assertCount(1, E::expandForPeriod([['dayOfMonth' => '1'] + $t], $this->half(1)));
        $this->assertSame([], E::expandForPeriod([['dayOfMonth' => '1'] + $t], $this->half(2)));
    }

    /**
     * Inactive absent.
     */
    public function test_inactive_absent(): void
    {
        $this->assertSame([], E::expandForPeriod([$this->t(['active' => false])], $this->half(1)));
    }

    /**
     * Flexible period expansion: repartido split and mensual_dia clamping.
     */
    public function test_expand_for_period(): void
    {
        $p = ['start' => '2026-11-16', 'end' => '2026-11-30', 'indexInMonth' => 1, 'partsInMonth' => 4];
        $rep = $this->t(['frequency' => 'mensual_repartido', 'estimated' => 100001]);
        $this->assertSame(25000, E::expandForPeriod([$rep], $p)[0]['estimated']);
        $this->assertSame(25001, E::expandForPeriod([$rep], ['indexInMonth' => 0] + $p)[0]['estimated']);
        $this->assertCount(1, E::expandForPeriod([$this->t(['frequency' => 'cada_periodo'])], $p));
        $dia = fn (int $d) => $this->t(['frequency' => 'mensual_dia', 'dayOfMonth' => $d]);
        $this->assertCount(1, E::expandForPeriod([$dia(16)], $p));
        $this->assertSame([], E::expandForPeriod([$dia(15)], $p));
        $this->assertCount(1, E::expandForPeriod([$dia(31)], $p));
        $this->assertCount(1, E::expandForPeriod([$dia(31)], ['start' => '2026-11-24', 'end' => '2026-11-30'] + $p));
        $this->assertSame([], E::expandForPeriod([$dia(31)], ['start' => '2026-11-01', 'end' => '2026-11-15'] + $p));
        $this->assertCount(1, E::expandForPeriod([$dia(2)], ['start' => '2026-10-29', 'end' => '2026-11-04'] + $p));
        $this->assertSame([], E::expandForPeriod([['active' => false] + $dia(16)], $p));
    }
}
