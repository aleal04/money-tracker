<?php

namespace Tests\Feature\Period;

use App\Services\Period\CuotasSyncService;

class CuotasSyncTest extends PeriodTestCase
{
    /**
     * Run the all-open sync.
     */
    private function sync(?int $periodId = null): void
    {
        $this->assertTrue(app(CuotasSyncService::class)->sync($periodId)->get('success'));
    }

    /**
     * New installments resync the line (552131 then 677861), and a paid line stays untouched.
     */
    public function test_resyncs_on_new_installments_keeping_paid_lines(): void
    {
        $card = $this->seedReloj();
        $p = $this->makePeriod();
        $this->assertSame('5521.31', $this->cuotasLines($p['id'])->sole()->estimated);

        $this->seedWallet($card);
        $this->sync();
        $line = $this->cuotasLines($p['id'])->sole();
        $this->assertSame(['6778.61', null], [$line->estimated, $line->actual]);

        $line->update(['paid' => true]);
        $this->seedWallet($card, 'X');
        $this->sync();
        $line = $this->cuotasLines($p['id'])->sole();
        $this->assertSame(['6778.61', true], [$line->estimated, $line->paid]);
    }

    /**
     * Sync updates only estimated, never the user's actual amount.
     */
    public function test_sync_keeps_actual(): void
    {
        $card = $this->seedReloj();
        $p = $this->makePeriod();
        $this->cuotasLines($p['id'])->sole()->update(['actual' => '6000.00']);

        $this->seedWallet($card);
        $this->sync();

        $line = $this->cuotasLines($p['id'])->sole();
        $this->assertSame(['6778.61', '6000.00'], [$line->estimated, $line->actual]);
    }

    /**
     * The all-open sync skips open periods older than the latest I quincena month.
     */
    public function test_all_open_sync_skips_periods_before_latest_first_half_month(): void
    {
        $card = $this->seedReloj();
        $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 2]);
        $this->seedWallet($card);
        $this->sync();
        $nov2 = $this->period(11, 2);
        $this->assertSame('6778.60', $this->cuotasLines($nov2->id)->sole()->estimated);

        $dec1 = $this->makePeriod(['year' => 2026, 'month' => 12, 'half' => 1]); // Reloj 13/24, Wallet finished
        $this->assertSame('5521.31', $this->cuotasLines($dec1['id'])->sole()->estimated);

        $this->seedInstallment($card, 'X', 'minicuotas', 18, 0, 100000 * 18, 100000, '2026-11');
        $this->sync();
        $this->assertSame('6778.60', $this->cuotasLines($nov2->id)->sole()->estimated);
        $this->assertSame('6021.31', $this->cuotasLines($dec1['id'])->sole()->estimated);
    }

    /**
     * A closed period is skipped even when targeted.
     */
    public function test_closed_period_is_skipped(): void
    {
        $card = $this->seedReloj();
        $p = $this->makePeriod();
        $this->period(11, 1)->update(['closed' => true]);
        $this->seedWallet($card);

        $this->sync($p['id']);

        $this->assertSame('5521.31', $this->cuotasLines($p['id'])->sole()->estimated);
    }
}
