<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\PeriodKeyHelper;

class PeriodKeyHelperTest extends TestCase
{
    /**
     * Labels use roman numerals and Spanish month names.
     */
    public function test_label(): void
    {
        $this->assertSame('I Quincena Octubre', PeriodKeyHelper::label(2026, 10, 1));
        $this->assertSame('II Quincena Octubre', PeriodKeyHelper::label(2026, 10, 2));
    }

    /**
     * next returns only the key fields and wraps the year.
     */
    public function test_next(): void
    {
        $this->assertSame(['year' => 2026, 'month' => 10, 'half' => 2], PeriodKeyHelper::next(2026, 10, 1));
        $this->assertSame(['year' => 2026, 'month' => 11, 'half' => 1], PeriodKeyHelper::next(2026, 10, 2));
        $this->assertSame(['year' => 2027, 'month' => 1, 'half' => 1], PeriodKeyHelper::next(2026, 12, 2));
    }

    /**
     * Month keys are zero padded and previous wraps the year.
     */
    public function test_month_keys(): void
    {
        $this->assertSame('2026-10', PeriodKeyHelper::monthKey(2026, 10));
        $this->assertSame('2026-03', PeriodKeyHelper::monthKey(2026, 3));
        $this->assertSame('2026-12', PeriodKeyHelper::prevMonthKey(2027, 1));
        $this->assertSame('2026-04', PeriodKeyHelper::prevMonthKey(2026, 5));
    }
}
