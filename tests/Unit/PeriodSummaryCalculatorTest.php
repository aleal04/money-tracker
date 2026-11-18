<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Period\PeriodSummaryCalculator as S;

class PeriodSummaryCalculatorTest extends TestCase
{
    /**
     * Build a line from a colones amount.
     *
     * @return array<string, mixed>
     */
    private function l(string $category, int $colones, bool $paid = true, bool $carried = false): array
    {
        return ['category' => $category, 'estimated' => $colones * 100, 'actual' => null, 'paid' => $paid, 'carried' => $carried];
    }

    /**
     * The Excel "I Quincena Octubre" fixture.
     *
     * @return array<int, array<string, mixed>>
     */
    private function octubre(bool $carried = false): array
    {
        return [
            $this->l('ingreso', 557632),
            $this->l('servicio', 10865), $this->l('servicio', 16146), $this->l('servicio', 68460), $this->l('servicio', 45000),
            $this->l('ahorro', 32580),
            $this->l('deuda_fija', 120000), $this->l('deuda_fija', 100013), $this->l('deuda_fija', 69132),
            $this->l('gasto', 5900, false, $carried), $this->l('gasto', 15000, false, $carried), $this->l('gasto', 10000, false, $carried), $this->l('gasto', 0, false),
        ];
    }

    public function test_excel_i_quincena_octubre(): void
    {
        $s = S::summarize($this->octubre());
        $this->assertSame(55763200, $s['ingreso']);
        $this->assertSame(49309600, $s['egreso']);
        $this->assertSame(6453600, $s['disponible']);
        $this->assertSame(3090000, $s['faltaPorPagar']);
        $this->assertSame(14047100, $s['byCategory']['servicio']['actual']);
    }

    public function test_carried_lines_excluded(): void
    {
        $s = S::summarize($this->octubre(true));
        $this->assertSame(46219600, $s['egreso']);
        $this->assertSame(0, $s['faltaPorPagar']);
    }

    public function test_category_totals_exclude_carried_and_add_up_to_egreso(): void
    {
        $s = S::summarize($this->octubre(true));
        $this->assertSame(['estimated' => 0, 'actual' => 0], $s['byCategory']['gasto']);
        $sum = 0;
        foreach (['servicio', 'gasto', 'ahorro', 'deuda_fija'] as $c) {
            $sum += $s['byCategory'][$c]['actual'];
        }
        $this->assertSame($s['egreso'], $sum);
    }

    public function test_ingreso_never_in_falta_por_pagar_and_actual_overrides(): void
    {
        $s = S::summarize([$this->l('ingreso', 100, false), ['actual' => 4000] + $this->l('gasto', 10, false)]);
        $this->assertSame(4000, $s['faltaPorPagar']);
        $this->assertSame(['estimated' => 1000, 'actual' => 4000], $s['byCategory']['gasto']);
    }
}
