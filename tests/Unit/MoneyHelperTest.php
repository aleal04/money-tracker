<?php

namespace Tests\Unit;

use App\Helpers\MoneyHelper;
use PHPUnit\Framework\TestCase;

class MoneyHelperTest extends TestCase
{
    /**
     * toCents and fromCents round-trip decimal strings without float math.
     */
    public function test_to_cents_and_from_cents(): void
    {
        $this->assertSame(1086500, MoneyHelper::toCents('10865.00'));
        $this->assertNull(MoneyHelper::toCents(null));
        $this->assertSame(1200, MoneyHelper::toCents(12));
        $this->assertSame('10865.00', MoneyHelper::fromCents(1086500));
        $this->assertSame('0.05', MoneyHelper::fromCents(5));
    }

    /**
     * The monthly split gives the extra cent to half 1.
     */
    public function test_split_monthly(): void
    {
        $this->assertSame(1168, MoneyHelper::splitMonthly(2335, 1));
        $this->assertSame(1167, MoneyHelper::splitMonthly(2335, 2));
        $this->assertSame(-1167, MoneyHelper::splitMonthly(-2335, 1));
        $this->assertSame(-1168, MoneyHelper::splitMonthly(-2335, 2));
        $this->assertSame(10001300, MoneyHelper::splitMonthly(10001300, 1) + MoneyHelper::splitMonthly(10001300, 2));
    }

    /**
     * parseAmount understands es-CR and plain decimal inputs.
     */
    public function test_parse_amount(): void
    {
        $this->assertSame(590000, MoneyHelper::parseAmount('5.900'));
        $this->assertSame(590050, MoneyHelper::parseAmount('5.900,50'));
        $this->assertSame(590050, MoneyHelper::parseAmount('5900,5'));
        $this->assertSame(590050, MoneyHelper::parseAmount('5900.5'));
        $this->assertSame(1086500, MoneyHelper::parseAmount('₡ 10.865'));
        $this->assertSame(123456789, MoneyHelper::parseAmount('1.234.567,89'));
        $this->assertNull(MoneyHelper::parseAmount('abc'));
        $this->assertNull(MoneyHelper::parseAmount(''));
        $this->assertNull(MoneyHelper::parseAmount('1.2.3'));
        $this->assertNull(MoneyHelper::parseAmount("\xB1"));
    }

    /**
     * format is deterministic and locale independent.
     */
    public function test_format(): void
    {
        $this->assertSame('₡10.865,00', MoneyHelper::format(1086500));
        $this->assertSame('-₡30.900,00', MoneyHelper::format(-3090000));
        $this->assertSame('₡0,05', MoneyHelper::format(5));
        $this->assertSame('₡1.234.567,89', MoneyHelper::format(123456789));
    }

    /**
     * Negative amounts are accepted by toCents but rejected by parseAmount.
     */
    public function test_negative_money(): void
    {
        $this->assertSame(-500, MoneyHelper::toCents('-5.00'));
        $this->assertSame(-1234, MoneyHelper::toCents('-12.34'));
        $this->assertSame('-0.05', MoneyHelper::fromCents(MoneyHelper::toCents('-0.05')));
        $this->assertNull(MoneyHelper::parseAmount('-5'));
    }

    /**
     * Split across parts sums back and gives the remainder to the first parts.
     */
    public function test_split_across(): void
    {
        $parts = array_map(fn (int $i) => MoneyHelper::splitAcross(100001, 4, $i), [0, 1, 2, 3]);
        $this->assertSame([25001, 25000, 25000, 25000], $parts);
        $this->assertSame(100001, array_sum($parts));
        $this->assertSame([1168, 1167], [MoneyHelper::splitAcross(2335, 2, 0), MoneyHelper::splitAcross(2335, 2, 1)]);
    }
}
