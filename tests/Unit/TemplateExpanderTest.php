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
        return $o + ['id' => 1, 'name' => 't', 'category' => 'gasto', 'estimated' => 1000000, 'frequency' => 'quincenal', 'half' => null, 'active' => true];
    }

    /**
     * Quincenal in both halves.
     */
    public function test_quincenal_in_both_halves(): void
    {
        $this->assertSame(1000000, E::expand([$this->t()], 1)[0]['estimated']);
        $this->assertSame(1000000, E::expand([$this->t()], 2)[0]['estimated']);
        $this->assertSame(['templateId' => 1, 'name' => 't', 'category' => 'gasto', 'estimated' => 1000000], E::expand([(object) $this->t()], 1)[0]);
    }

    /**
     * Mensual dividido splits.
     */
    public function test_mensual_dividido_splits(): void
    {
        $t = $this->t(['frequency' => 'mensual_dividido', 'estimated' => 2335]);
        $this->assertSame(1168, E::expand([$t], 1)[0]['estimated']);
        $this->assertSame(1167, E::expand([$t], 2)[0]['estimated']);
    }

    /**
     * Mensual en quincena only in its half.
     */
    public function test_mensual_en_quincena_only_in_its_half(): void
    {
        $t = $this->t(['frequency' => 'mensual_en_quincena', 'half' => 2]);
        $this->assertSame([], E::expand([$t], 1));
        $this->assertCount(1, E::expand([$t], 2));
        $this->assertCount(1, E::expand([['half' => '2'] + $t], 2));
        $this->assertSame([], E::expand([['half' => '1'] + $t], 2));
    }

    /**
     * Inactive absent.
     */
    public function test_inactive_absent(): void
    {
        $this->assertSame([], E::expand([$this->t(['active' => false])], 1));
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
