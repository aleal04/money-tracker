<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use App\Constant\SpaceFrequencyConstant;

/**
 * Period date rules per space frequency. Static style; dates are 'Y-m-d' strings.
 *
 * Known limit (monthly): a period starting on a day past 28 drifts, because only the previous end date is known.
 * Starts on the last day of a month (day 28 or later) are treated as month-end anchored (31 Jan -> 28 Feb -> 31 Mar),
 * so a 28 Feb start meant as "day 28" also jumps to the end of March; other days past 28 clamp and then re-anchor.
 */
final class PeriodScheduleHelper
{
    private const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    private const SHORT = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    /**
     * Propose the next period: starts the day after $lastEndDate (or aligned around $today when there is none).
     *
     * @return array{start:string,end:string}
     */
    public static function proposeNext(string $frequency, ?int $customDays, ?string $lastEndDate, string $today): array
    {
        $start = $lastEndDate !== null ? CarbonImmutable::parse($lastEndDate)->addDay() : self::firstStart($frequency, CarbonImmutable::parse($today));
        $end = match ($frequency) {
            SpaceFrequencyConstant::BIWEEKLY => $start->day === 1 ? $start->day(15) : ($start->day === 16 ? $start->endOfMonth() : $start->addDays(14)),
            SpaceFrequencyConstant::MONTHLY => match (true) {
                $start->day === 1 => $start->endOfMonth(),
                $start->day >= 28 && $start->isLastOfMonth() => $start->addMonthNoOverflow()->endOfMonth()->subDay(),
                default => $start->addMonthNoOverflow()->subDay(),
            },
            SpaceFrequencyConstant::WEEKLY => $start->addDays(6),
            default => $start->addDays(max(1, (int) $customDays) - 1),
        };

        return ['start' => $start->toDateString(), 'end' => $end->startOfDay()->toDateString()];
    }

    /**
     * Start of the first period: calendar-aligned for biweekly/monthly, today for weekly/custom.
     */
    private static function firstStart(string $frequency, CarbonImmutable $today): CarbonImmutable
    {
        return match ($frequency) {
            SpaceFrequencyConstant::BIWEEKLY => $today->day <= 15 ? $today->startOfMonth() : $today->day(16),
            SpaceFrequencyConstant::MONTHLY => $today->startOfMonth(),
            default => $today,
        };
    }

    /**
     * Human label for a period, e.g. "I Quincena Octubre 2026", "Semana 12–18 nov 2026", "12 nov – 26 nov 2026".
     */
    public static function label(string $frequency, string $start, string $end): string
    {
        $s = CarbonImmutable::parse($start);
        $e = CarbonImmutable::parse($end);
        $month = self::MONTHS[$s->month - 1].' '.$s->year;
        $sameMonth = $s->format('Y-m') === $e->format('Y-m');
        $monthEnd = $e->toDateString() === $s->endOfMonth()->toDateString();
        $half = self::half($frequency, $start, $end);
        if ($half !== null) {
            return ($half === 1 ? 'I' : 'II').' Quincena '.$month;
        }
        if ($frequency === SpaceFrequencyConstant::MONTHLY && $s->day === 1 && $monthEnd) {
            return $month;
        }
        if ($frequency === SpaceFrequencyConstant::WEEKLY && $sameMonth) {
            return 'Semana '.$s->day.'–'.$e->day.' '.self::SHORT[$e->month - 1].' '.$e->year;
        }
        $a = $s->day.' '.self::SHORT[$s->month - 1].($s->year !== $e->year ? ' '.$s->year : '');

        return $a.' – '.$e->day.' '.self::SHORT[$e->month - 1].' '.$e->year;
    }

    /**
     * The quincena (1 or 2) of a biweekly period on calendar halves (1–15, 16–end of month); null otherwise.
     */
    public static function half(string $frequency, string $start, string $end): ?int
    {
        $s = CarbonImmutable::parse($start);
        $e = CarbonImmutable::parse($end);

        return match (true) {
            $frequency !== SpaceFrequencyConstant::BIWEEKLY || $s->format('Y-m') !== $e->format('Y-m') => null,
            $s->day === 1 && $e->day === 15 => 1,
            $s->day === 16 && $e->day === $e->daysInMonth => 2,
            default => null,
        };
    }

    /**
     * 0-based position of the period starting at $start among the projected periods that start in its month
     * (biweekly on calendar halves: 0 for day 1, 1 for day 16; monthly: 0; otherwise steps back that stay in the month,
     * biweekly off the halves stepping 14 days like proposeNext does).
     */
    public static function indexInMonth(string $frequency, ?int $customDays, string $start): int
    {
        $d = CarbonImmutable::parse($start);
        if ($frequency === SpaceFrequencyConstant::BIWEEKLY && in_array($d->day, [1, 16], true)) {
            return $d->day === 1 ? 0 : 1;
        }
        if ($frequency === SpaceFrequencyConstant::MONTHLY) {
            return 0;
        }

        return intdiv($d->day - 1, self::step($frequency, $customDays));
    }

    /**
     * Projected number of periods that start in the month of $anyStartInMonth.
     */
    public static function periodsStartingInMonth(string $frequency, ?int $customDays, string $anyStartInMonth): int
    {
        $d = CarbonImmutable::parse($anyStartInMonth);
        if ($frequency === SpaceFrequencyConstant::MONTHLY) {
            return 1;
        }
        if ($frequency === SpaceFrequencyConstant::BIWEEKLY && in_array($d->day, [1, 16], true)) {
            return 2;
        }
        $step = self::step($frequency, $customDays);
        $ym = $d->format('Y-m');
        while ($d->subDays($step)->format('Y-m') === $ym) {
            $d = $d->subDays($step);
        }
        for ($n = 0; $d->format('Y-m') === $ym; $d = $d->addDays($step)) {
            $n++;
        }

        return $n;
    }

    /**
     * Days between period starts of a projected chain: weekly 7, custom its length, biweekly off the calendar halves 14.
     */
    private static function step(string $frequency, ?int $customDays): int
    {
        return match ($frequency) {
            SpaceFrequencyConstant::BIWEEKLY => 14,
            SpaceFrequencyConstant::WEEKLY => 7,
            default => max(1, (int) $customDays),
        };
    }
}
