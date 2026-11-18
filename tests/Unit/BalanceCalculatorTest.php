<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Period\BalanceCalculator;

class BalanceCalculatorTest extends TestCase
{
    /**
     * Chain starts at the initial balance and goes negative; carried egreso is already excluded by the caller.
     */
    public function test_chain_with_negative_and_carry(): void
    {
        $r = BalanceCalculator::chain(100, [
            ['ingreso' => 1000, 'egreso' => 1500],
            ['ingreso' => 0, 'egreso' => 200],
            ['ingreso' => 900, 'egreso' => 300],
        ]);
        $this->assertSame([['saldoAnterior' => 100, 'restante' => -400], ['saldoAnterior' => -400, 'restante' => -600], ['saldoAnterior' => -600, 'restante' => 0]], $r);
        $this->assertSame([], BalanceCalculator::chain(5, []));
    }
}
