<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Helpers\PeriodScheduleHelper as P;

class PeriodScheduleHelperTest extends TestCase
{
    /**
     * Biweekly follows calendar halves, including February and leap year.
     */
    public function test_biweekly_halves(): void
    {
        $this->assertSame(['start' => '2026-10-16', 'end' => '2026-10-31'], P::proposeNext('biweekly', null, '2026-10-15', '2026-10-20'));
        $this->assertSame(['start' => '2026-11-01', 'end' => '2026-11-15'], P::proposeNext('biweekly', null, '2026-10-31', '2026-10-20'));
        $this->assertSame(['start' => '2026-02-16', 'end' => '2026-02-28'], P::proposeNext('biweekly', null, '2026-02-15', '2026-02-20'));
        $this->assertSame(['start' => '2028-02-16', 'end' => '2028-02-29'], P::proposeNext('biweekly', null, '2028-02-15', '2028-02-20'));
        $this->assertSame(['start' => '2026-10-10', 'end' => '2026-10-24'], P::proposeNext('biweekly', null, '2026-10-09', '2026-10-20'));
    }

    /**
     * Monthly aligned goes to EOM, non-aligned is one month minus a day.
     */
    public function test_monthly(): void
    {
        $this->assertSame(['start' => '2026-11-01', 'end' => '2026-11-30'], P::proposeNext('monthly', null, '2026-10-31', '2026-10-31'));
        $this->assertSame(['start' => '2026-11-10', 'end' => '2026-12-09'], P::proposeNext('monthly', null, '2026-11-09', '2026-11-09'));
    }

    /**
     * Weekly crossing a month and custom days.
     */
    public function test_weekly_and_custom(): void
    {
        $this->assertSame(['start' => '2026-10-29', 'end' => '2026-11-04'], P::proposeNext('weekly', null, '2026-10-28', '2026-10-28'));
        $this->assertSame(['start' => '2026-11-01', 'end' => '2026-11-10'], P::proposeNext('custom', 10, '2026-10-31', '2026-10-31'));
    }

    /**
     * First period contains today, aligned per frequency.
     */
    public function test_first_period(): void
    {
        $this->assertSame(['start' => '2026-11-16', 'end' => '2026-11-30'], P::proposeNext('biweekly', null, null, '2026-11-18'));
        $this->assertSame(['start' => '2026-11-01', 'end' => '2026-11-30'], P::proposeNext('monthly', null, null, '2026-11-18'));
        $this->assertSame(['start' => '2026-11-18', 'end' => '2026-11-24'], P::proposeNext('weekly', null, null, '2026-11-18'));
        $this->assertSame(['start' => '2026-11-18', 'end' => '2026-11-20'], P::proposeNext('custom', 3, null, '2026-11-18'));
    }

    /**
     * Labels per frequency, including cross-month and cross-year.
     */
    public function test_labels(): void
    {
        $this->assertSame('I Quincena Octubre 2026', P::label('biweekly', '2026-10-01', '2026-10-15'));
        $this->assertSame('II Quincena Octubre 2026', P::label('biweekly', '2026-10-16', '2026-10-31'));
        $this->assertSame('Octubre 2026', P::label('monthly', '2026-10-01', '2026-10-31'));
        $this->assertSame('Semana 12–18 nov 2026', P::label('weekly', '2026-11-12', '2026-11-18'));
        $this->assertSame('29 oct – 4 nov 2026', P::label('weekly', '2026-10-29', '2026-11-04'));
        $this->assertSame('12 nov – 26 nov 2026', P::label('custom', '2026-11-12', '2026-11-26'));
        $this->assertSame('10 oct – 24 oct 2026', P::label('biweekly', '2026-10-10', '2026-10-24'));
        $this->assertSame('28 dic 2026 – 3 ene 2027', P::label('weekly', '2026-12-28', '2027-01-03'));
    }

    /**
     * Projected counts per month.
     */
    public function test_periods_starting_in_month(): void
    {
        $this->assertSame(2, P::periodsStartingInMonth('biweekly', null, '2026-11-01'));
        $this->assertSame(1, P::periodsStartingInMonth('monthly', null, '2026-11-01'));
        $this->assertSame(5, P::periodsStartingInMonth('weekly', null, '2026-10-01'));
        $this->assertSame(4, P::periodsStartingInMonth('weekly', null, '2026-11-12'));
        $this->assertSame(3, P::periodsStartingInMonth('custom', 10, '2026-11-11'));
    }
}
