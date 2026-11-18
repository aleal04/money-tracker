<?php

namespace App\Helpers;

/**
 * Integer-cents money helpers. Static style; never uses float arithmetic on amounts.
 */
final class MoneyHelper
{
    /**
     * Parse a "123.45" style decimal string into integer cents without float math.
     */
    private static function decimalToCents(string $s, bool $allowNegative): ?int
    {
        $re = $allowNegative ? '/^(-?)(\d+)(?:\.(\d{1,2}))?$/' : '/^()(\d+)(?:\.(\d{1,2}))?$/';
        if (! preg_match($re, $s, $m)) {
            return null;
        }
        $c = (int) $m[2] * 100 + (int) str_pad($m[3] ?? '', 2, '0');

        return $m[1] !== '' ? -$c : $c;
    }

    /**
     * Convert a decimal string or integer (major units) to cents; null stays null, invalid gives null.
     */
    public static function toCents(string|int|null $v): ?int
    {
        if ($v === null) {
            return null;
        }

        return self::decimalToCents(is_string($v) ? trim($v) : number_format($v, 2, '.', ''), true);
    }

    /**
     * Like toCents but throws on null or an invalid value, so bad data fails loudly.
     */
    public static function requireCents(string|int|null $v): int
    {
        return self::toCents($v) ?? throw new \InvalidArgumentException('Monto inválido: '.var_export($v, true));
    }

    /**
     * Convert cents to a plain decimal string, e.g. 1086500 -> "10865.00".
     */
    public static function fromCents(int $c): string
    {
        $a = abs($c);

        return ($c < 0 ? '-' : '').intdiv($a, 100).'.'.str_pad((string) ($a % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Format cents as colones: "₡10.865,00" / "-₡30.900,00" (dot thousands, comma decimals).
     */
    public static function format(int $c): string
    {
        $a = abs($c);
        $int = number_format(intdiv($a, 100), 0, '', '.');

        return ($c < 0 ? '-' : '').'₡'.$int.','.str_pad((string) ($a % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Parse user input in es-CR style ("5.900,50", "5900.5", "₡ 10.865") into non-negative cents; null if invalid.
     */
    public static function parseAmount(string $input): ?int
    {
        $s = preg_replace('/₡|\s/u', '', $input) ?? '';
        if (str_contains($s, ',')) {
            $s = str_replace(',', '.', str_replace('.', '', $s));
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
            $s = str_replace('.', '', $s);
        }

        return self::decimalToCents($s, false);
    }

    /**
     * Split a monthly amount between halves: half 1 gets the ceil, half 2 the floor.
     */
    public static function splitMonthly(int $cents, int $half): int
    {
        return $half === 1 ? $cents - ($cents >> 1) : $cents >> 1;
    }

    /**
     * Split cents into equal parts; the remainder cents go one each to the first parts (index is 0-based).
     */
    public static function splitAcross(int $cents, int $parts, int $index): int
    {
        return intdiv($cents, $parts) + ($index < $cents % $parts ? 1 : 0);
    }
}
