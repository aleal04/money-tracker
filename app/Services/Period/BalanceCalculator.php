<?php

namespace App\Services\Period;

/**
 * Running balance across ordered periods of a space. Static style.
 */
final class BalanceCalculator
{
    /**
     * Chain balances: each period's saldoAnterior is the previous restante (the first uses the initial balance).
     * Egreso must already exclude carried lines, so arrastre is never double counted. Negatives propagate.
     *
     * @param  iterable<array{ingreso:int,egreso:int}>  $periodsSummaries  ordered by StartDate
     * @return array<int, array{saldoAnterior:int,restante:int}>
     */
    public static function chain(int $initialBalance, iterable $periodsSummaries): array
    {
        $out = [];
        $prev = $initialBalance;
        foreach ($periodsSummaries as $s) {
            $s = (object) $s;
            $rest = $prev + $s->ingreso - $s->egreso;
            $out[] = ['saldoAnterior' => $prev, 'restante' => $rest];
            $prev = $rest;
        }

        return $out;
    }
}
