<?php

namespace App\Services\Template;

use Carbon\CarbonImmutable;
use App\Helpers\MoneyHelper;
use App\Constant\TemplateFrequencyConstant;

/**
 * Expands recurring templates into period lines. Static style. Templates are arrays or objects with
 * id, name, category, estimated, frequency, half, active.
 */
final class TemplateExpander
{
    /**
     * Lines a template set produces for the given half.
     *
     * @param  iterable<array<string, mixed>|object>  $templates
     * @return array<int, array{templateId:mixed,name:mixed,category:mixed,estimated:int}>
     */
    public static function expand(iterable $templates, int $half): array
    {
        $out = [];
        foreach ($templates as $t) {
            $t = (object) $t;
            if (! $t->active) {
                continue;
            }
            if ($t->frequency === TemplateFrequencyConstant::MENSUAL_EN_QUINCENA && (int) $t->half !== $half) {
                continue;
            }
            $est = $t->frequency === TemplateFrequencyConstant::MENSUAL_DIVIDIDO ? MoneyHelper::splitMonthly($t->estimated, $half) : $t->estimated;
            $out[] = ['templateId' => $t->id, 'name' => $t->name, 'category' => $t->category, 'estimated' => $est];
        }

        return $out;
    }

    /**
     * Lines a template set produces for a flexible period.
     *
     * @param  iterable<array<string, mixed>|object>  $templates  with id, name, category, estimated, frequency, dayOfMonth, active
     * @param  array{start:string,end:string,indexInMonth:int,partsInMonth:int}  $period  (extra keys such as frequency are ignored)
     * @return array<int, array{templateId:mixed,name:mixed,category:mixed,estimated:int}>
     */
    public static function expandForPeriod(iterable $templates, array $period): array
    {
        $out = [];
        foreach ($templates as $t) {
            $t = (object) $t;
            if (! $t->active) {
                continue;
            }
            $est = $t->estimated;
            if ($t->frequency === TemplateFrequencyConstant::MENSUAL_REPARTIDO) {
                $est = MoneyHelper::splitAcross($t->estimated, $period['partsInMonth'], $period['indexInMonth']);
            } elseif ($t->frequency === TemplateFrequencyConstant::MENSUAL_DIA && ! self::containsDay((int) $t->dayOfMonth, $period['start'], $period['end'])) {
                continue;
            }
            $out[] = ['templateId' => $t->id, 'name' => $t->name, 'category' => $t->category, 'estimated' => $est];
        }

        return $out;
    }

    /**
     * Whether day-of-month N (clamped to each month's last day) falls inside [start, end].
     */
    private static function containsDay(int $day, string $start, string $end): bool
    {
        $s = CarbonImmutable::parse($start);
        $e = CarbonImmutable::parse($end);
        foreach ([$s, $e] as $m) {
            $d = $m->startOfMonth()->addDays(min($day, $m->daysInMonth) - 1);
            if ($d >= $s && $d <= $e) {
                return true;
            }
        }

        return false;
    }
}
