<?php

namespace App\Services\Template;

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
            if ($t->frequency === TemplateFrequencyConstant::MENSUAL_EN_QUINCENA && $t->half !== $half) {
                continue;
            }
            $est = $t->frequency === TemplateFrequencyConstant::MENSUAL_DIVIDIDO ? MoneyHelper::splitMonthly($t->estimated, $half) : $t->estimated;
            $out[] = ['templateId' => $t->id, 'name' => $t->name, 'category' => $t->category, 'estimated' => $est];
        }

        return $out;
    }
}
