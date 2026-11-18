<?php

namespace App\Helpers;

use Carbon\CarbonImmutable;
use App\Constant\SpaceFrequencyConstant;

/**
 * Period date rules per space frequency. Static style; dates are 'Y-m-d' strings.
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
            SpaceFrequencyConstant::MONTHLY => $start->day === 1 ? $start->endOfMonth() : $start->addMonthNoOverflow()->subDay(),
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
        if ($frequency === SpaceFrequencyConstant::BIWEEKLY && $sameMonth) {
            if ($s->day === 1 && $e->day === 15) {
                return 'I Quincena '.$month;
            }
            if ($s->day === 16 && $monthEnd) {
                return 'II Quincena '.$month;
            }
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
     * Projected number of periods that start in the month of $anyStartInMonth.
     */
    public static function periodsStartingInMonth(string $frequency, ?int $customDays, string $anyStartInMonth): int
    {
        if ($frequency === SpaceFrequencyConstant::BIWEEKLY) {
            return 2;
        }
        if ($frequency === SpaceFrequencyConstant::MONTHLY) {
            return 1;
        }
        $step = $frequency === SpaceFrequencyConstant::WEEKLY ? 7 : max(1, (int) $customDays);
        $d = CarbonImmutable::parse($anyStartInMonth);
        $ym = $d->format('Y-m');
        while ($d->subDays($step)->format('Y-m') === $ym) {
            $d = $d->subDays($step);
        }
        for ($n = 0; $d->format('Y-m') === $ym; $d = $d->addDays($step)) {
            $n++;
        }

        return $n;
    }
}
