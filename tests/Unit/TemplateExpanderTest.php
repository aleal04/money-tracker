<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\Template\TemplateExpander as E;

class TemplateExpanderTest extends TestCase
{
    /**
     * Build a template with defaults.
     *
     * @param  array<string, mixed>  $o
     * @return array<string, mixed>
     */
    private function t(array $o = []): array
    {
        return $o + ['id' => 1, 'name' => 't', 'category' => 'gasto', 'estimated' => 1000000, 'frequency' => 'quincenal', 'half' => null, 'active' => true];
    }

    public function test_quincenal_in_both_halves(): void
    {
        $this->assertSame(1000000, E::expand([$this->t()], 1)[0]['estimated']);
        $this->assertSame(1000000, E::expand([$this->t()], 2)[0]['estimated']);
        $this->assertSame(['templateId' => 1, 'name' => 't', 'category' => 'gasto', 'estimated' => 1000000], E::expand([(object) $this->t()], 1)[0]);
    }

    public function test_mensual_dividido_splits(): void
    {
        $t = $this->t(['frequency' => 'mensual_dividido', 'estimated' => 2335]);
        $this->assertSame(1168, E::expand([$t], 1)[0]['estimated']);
        $this->assertSame(1167, E::expand([$t], 2)[0]['estimated']);
    }

    public function test_mensual_en_quincena_only_in_its_half(): void
    {
        $t = $this->t(['frequency' => 'mensual_en_quincena', 'half' => 2]);
        $this->assertSame([], E::expand([$t], 1));
        $this->assertCount(1, E::expand([$t], 2));
    }

    public function test_inactive_absent(): void
    {
        $this->assertSame([], E::expand([$this->t(['active' => false])], 1));
    }
}
