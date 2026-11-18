<?php

namespace App\Services\Period;

use App\Constant\CategoryConstant;

/**
 * Quincena summary in cents. Static style. Lines are arrays or objects with
 * category, estimated, actual, paid, carried.
 */
final class PeriodSummaryCalculator
{
    /**
     * Effective value of a line: actual when set, else estimated.
     *
     * @param  array<string, mixed>|object  $l
     */
    public static function lineValue(array|object $l): int
    {
        $l = (object) $l;

        return $l->actual ?? $l->estimated;
    }

    /**
     * Totals for a period. Carried lines are excluded from everything; ingreso never counts as faltaPorPagar.
     *
     * @param  iterable<array<string, mixed>|object>  $lines
     * @return array{ingreso:int,egreso:int,disponible:int,faltaPorPagar:int,byCategory:array<string,array{estimated:int,actual:int}>}
     */
    public static function summarize(iterable $lines): array
    {
        $by = [];
        foreach (CategoryConstant::ALL as $c) {
            $by[$c] = ['estimated' => 0, 'actual' => 0];
        }
        $ingreso = $egreso = $falta = 0;
        foreach ($lines as $l) {
            $l = (object) $l;
            if ($l->carried) {
                continue;
            }
            $v = self::lineValue($l);
            $by[$l->category]['estimated'] += $l->estimated;
            $by[$l->category]['actual'] += $v;
            if ($l->category === CategoryConstant::INGRESO) {
                $ingreso += $v;
            } else {
                $egreso += $v;
                if (! $l->paid) {
                    $falta += $v;
                }
            }
        }

        return ['ingreso' => $ingreso, 'egreso' => $egreso, 'disponible' => $ingreso - $egreso, 'faltaPorPagar' => $falta, 'byCategory' => $by];
    }
}
