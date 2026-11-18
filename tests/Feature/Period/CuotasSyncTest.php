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
        $this->assertSame('5521.31', $this->cuotasLines($p['id'])->sole()->Estimated);

        $this->seedWallet($card);
        $this->sync();
        $line = $this->cuotasLines($p['id'])->sole();
        $this->assertSame(['6778.61', null], [$line->Estimated, $line->Actual]);

        $line->update(['Paid' => true]);
        $this->seedWallet($card, 'X');
        $this->sync();
        $line = $this->cuotasLines($p['id'])->sole();
        $this->assertSame(['6778.61', true], [$line->Estimated, $line->Paid]);
    }

    /**
     * Sync updates only estimated, never the user's actual amount.
     */
    public function test_sync_keeps_actual(): void
    {
        $card = $this->seedReloj();
        $p = $this->makePeriod();
        $this->cuotasLines($p['id'])->sole()->update(['Actual' => '6000.00']);

        $this->seedWallet($card);
        $this->sync();

        $line = $this->cuotasLines($p['id'])->sole();
        $this->assertSame(['6778.61', '6000.00'], [$line->Estimated, $line->Actual]);
    }

    /**
     * The all-open sync skips open periods older than the latest month of the space.
     */
    public function test_all_open_sync_skips_periods_before_the_latest_month(): void
    {
        $card = $this->seedReloj();
        $this->makePeriod($this->dates(11, 2));
        $this->seedWallet($card);
        $this->sync();
        $nov2 = $this->period(11, 2);
        $this->assertSame('6778.60', $this->cuotasLines($nov2->IdPeriod)->sole()->Estimated);

        $dec1 = $this->makePeriod($this->dates(12, 1)); // Reloj 13/24, Wallet finished
        $this->assertSame('5521.31', $this->cuotasLines($dec1['id'])->sole()->Estimated);

        $this->seedInstallment($card, 'X', 'minicuotas', 18, 0, 100000 * 18, 100000, '2026-11');
        $this->sync();
        $this->assertSame('6778.60', $this->cuotasLines($nov2->IdPeriod)->sole()->Estimated);
        $this->assertSame('6021.31', $this->cuotasLines($dec1['id'])->sole()->Estimated);
    }

    /**
     * A closed period is skipped even when targeted.
     */
    public function test_closed_period_is_skipped(): void
    {
        $card = $this->seedReloj();
        $p = $this->makePeriod();
        $this->period(11, 1)->update(['Closed' => true]);
        $this->seedWallet($card);

        $this->sync($p['id']);

        $this->assertSame('5521.31', $this->cuotasLines($p['id'])->sole()->Estimated);
    }
}
