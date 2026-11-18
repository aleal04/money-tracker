<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\PeriodKeyHelper;

class PeriodKeyHelperTest extends TestCase
{
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
