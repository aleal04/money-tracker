<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Installment\InstallmentCalculator as C;

class InstallmentCalculatorTest extends TestCase
{
    /**
     * Build an installment input with defaults.
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    private function mk(array $o = []): array
    {
        return $o + ['type' => 'tasa0', 'totalInstallments' => 24, 'paidInstallments' => 0, 'initialBalance' => 0, 'installmentAmount' => null, 'balanceOverride' => null];
    }

    public function test_tasa0_reloj(): void
    {
        $i = $this->mk(['initialBalance' => 26502262, 'paidInstallments' => 12]);
        $this->assertSame(1104261, C::cuota($i));
        $this->assertSame(13251131, C::saldoFaltanteCalculado($i));
    }

    public function test_tasa0_iphone(): void
    {
        $i = $this->mk(['initialBalance' => 76918464, 'paidInstallments' => 22]);
        $this->assertSame(3204936, C::cuota($i));
        $this->assertSame(6409872, C::saldoFaltanteCalculado($i));
    }

    public function test_minicuotas_and_object_input(): void
    {
        $this->assertSame(59266620, C::saldoFaltanteCalculado($this->mk(['type' => 'minicuotas', 'installmentAmount' => 2822220, 'paidInstallments' => 3])));
        $this->assertSame(14277840, C::saldoFaltanteCalculado((object) $this->mk(['type' => 'minicuotas', 'installmentAmount' => 594910])));
    }

    public function test_override_wins(): void
    {
        $i = $this->mk(['type' => 'minicuotas', 'installmentAmount' => 2546500, 'paidInstallments' => 10, 'balanceOverride' => 29438200]);
        $this->assertSame(['value' => 29438200, 'calculated' => 35651000, 'diff' => -6212800], C::saldoFaltante($i));
        $this->assertNull(C::saldoFaltante(['balanceOverride' => null] + $i)['diff']);
    }

    public function test_never_negative(): void
    {
        $this->assertSame(0, C::saldoFaltanteCalculado($this->mk(['initialBalance' => 1000, 'paidInstallments' => 30])));
    }

    public function test_status(): void
    {
        $s = fn (int $total, int $paid) => C::status($this->mk(['totalInstallments' => $total, 'paidInstallments' => $paid]));
        $this->assertSame('terminada', $s(12, 12));
        $this->assertSame('por_terminar', $s(18, 17));
        $this->assertSame('por_terminar', $s(24, 23));
        $this->assertSame('por_terminar', $s(24, 21));
        $this->assertSame('activa', $s(24, 20));
    }

    public function test_advance(): void
    {
        $this->assertSame(['paid' => 13, 'lastAdvanced' => '2026-10'], C::advance(12, 24, '2026-09', '2026-10'));
        $this->assertSame(['paid' => 13, 'lastAdvanced' => '2026-10'], C::advance(13, 24, '2026-10', '2026-10'));
        $this->assertSame(14, C::advance(12, 24, '2026-08', '2026-10')['paid']);
        $this->assertSame(24, C::advance(23, 24, '2026-08', '2026-10')['paid']);
        $this->assertSame(['paid' => 12, 'lastAdvanced' => '2026-10'], C::advance(12, 24, '2026-10', '2026-08'));
        $this->assertSame(7, C::advance(5, 12, '2026-11', '2027-01')['paid']);
    }

    public function test_cuotas_line_amount_skips_finished_and_splits(): void
    {
        $list = [
            $this->mk(['initialBalance' => 26502262, 'paidInstallments' => 12]),
            $this->mk(['initialBalance' => 5000000, 'totalInstallments' => 12, 'paidInstallments' => 12]),
            $this->mk(['type' => 'minicuotas', 'totalInstallments' => 18, 'paidInstallments' => 17, 'installmentAmount' => 251460]),
        ];
        $this->assertSame(677861, C::cuotasLineAmount($list, 1));
        $this->assertSame(677860, C::cuotasLineAmount($list, 2));
    }
}
