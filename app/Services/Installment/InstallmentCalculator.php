<?php

namespace App\Services\Installment;

use App\Helpers\MoneyHelper;
use App\Constant\InstallmentTypeConstant;
use App\Constant\InstallmentStatusConstant;

/**
 * Installment math in cents. Static style. Inputs are arrays or objects with keys
 * type, totalInstallments, paidInstallments, initialBalance, installmentAmount, balanceOverride.
 */
final class InstallmentCalculator
{
    /**
     * Remaining cuotas, never negative.
     */
    private static function remaining(object $i): int
    {
        return max($i->totalInstallments - $i->paidInstallments, 0);
    }

    /**
     * Round a non-negative a/b half up with integer math.
     */
    private static function divRound(int $a, int $b): int
    {
        return intdiv(2 * $a + $b, 2 * $b);
    }

    /**
     * Monthly cuota in cents.
     *
     * @param  array<string, mixed>|object  $i
     */
    public static function cuota(array|object $i): int
    {
        $i = (object) $i;

        return $i->type === InstallmentTypeConstant::TASA0 ? self::divRound($i->initialBalance, $i->totalInstallments) : ($i->installmentAmount ?? 0);
    }

    /**
     * Calculated outstanding balance in cents (tasa0 uses the unrounded cuota to match Excel).
     *
     * @param  array<string, mixed>|object  $i
     */
    public static function saldoFaltanteCalculado(array|object $i): int
    {
        $i = (object) $i;

        return $i->type === InstallmentTypeConstant::TASA0
            ? self::divRound($i->initialBalance * self::remaining($i), $i->totalInstallments)
            : self::cuota($i) * self::remaining($i);
    }

    /**
     * Outstanding balance honoring the manual override.
     *
     * @param  array<string, mixed>|object  $i
     * @return array{value:int,calculated:int,diff:?int}
     */
    public static function saldoFaltante(array|object $i): array
    {
        $i = (object) $i;
        $calc = self::saldoFaltanteCalculado($i);
        $o = $i->balanceOverride ?? null;

        return $o === null ? ['value' => $calc, 'calculated' => $calc, 'diff' => null] : ['value' => $o, 'calculated' => $calc, 'diff' => $o - $calc];
    }

    /**
     * Status: terminada, por_terminar or activa.
     *
     * @param  array<string, mixed>|object  $i
     */
    public static function status(array|object $i): string
    {
        $r = self::remaining((object) $i);

        return $r === 0 ? InstallmentStatusConstant::TERMINADA : ($r <= InstallmentStatusConstant::POR_TERMINAR_THRESHOLD ? InstallmentStatusConstant::POR_TERMINAR : InstallmentStatusConstant::ACTIVA);
    }

    /**
     * Advance paid cuotas up to the target month ("YYYY-MM"); lastAdvanced moves even when capped.
     *
     * @return array{paid:int,lastAdvanced:string}
     */
    public static function advance(int $paid, int $total, string $last, string $target): array
    {
        $idx = fn (string $k) => (int) substr($k, 0, 4) * 12 + (int) substr($k, 5, 2);
        $months = $idx($target) - $idx($last);

        return $months <= 0 ? ['paid' => $paid, 'lastAdvanced' => $last] : ['paid' => min($paid + $months, $total), 'lastAdvanced' => $target];
    }

    /**
     * Amount of the "Cuotas" line for a half: unfinished cuotas summed, then split monthly.
     *
     * @param  iterable<array<string, mixed>|object>  $installments
     */
    public static function cuotasLineAmount(iterable $installments, int $half): int
    {
        $sum = 0;
        foreach ($installments as $i) {
            $o = (object) $i;
            if ($o->paidInstallments < $o->totalInstallments) {
                $sum += self::cuota($o);
            }
        }

        return MoneyHelper::splitMonthly($sum, $half);
    }
}
