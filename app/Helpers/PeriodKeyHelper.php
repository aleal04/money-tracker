<?php

namespace App\Helpers;

/**
 * Quincena period key helpers. Static style.
 */
final class PeriodKeyHelper
{
    private const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    /**
     * Human label, e.g. "I Quincena Octubre".
     */
    public static function label(int $y, int $m, int $h): string
    {
        return ($h === 1 ? 'I' : 'II').' Quincena '.self::MONTHS[$m - 1];
    }

    /**
     * The period following the given one, wrapping month and year.
     *
     * @return array{year:int,month:int,half:int}
     */
    public static function next(int $y, int $m, int $h): array
    {
        if ($h === 1) {
            return ['year' => $y, 'month' => $m, 'half' => 2];
        }

        return $m === 12 ? ['year' => $y + 1, 'month' => 1, 'half' => 1] : ['year' => $y, 'month' => $m + 1, 'half' => 1];
    }

    /**
     * Month key "YYYY-MM".
     */
    public static function monthKey(int $y, int $m): string
    {
        return sprintf('%d-%02d', $y, $m);
    }

    /**
     * Month key of the previous month.
     */
    public static function prevMonthKey(int $y, int $m): string
    {
        return $m === 1 ? self::monthKey($y - 1, 12) : self::monthKey($y, $m - 1);
    }
}
