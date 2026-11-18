<?php

namespace App\Helpers;

/**
 * Month key helpers ("YYYY-MM", used by the installment advance). Static style.
 */
final class PeriodKeyHelper
{
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
