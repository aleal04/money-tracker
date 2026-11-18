<?php

namespace Tests\Feature\Database;

use Tests\TestCase;
use App\Helpers\MoneyHelper;
use App\Models\Period\LineModel;
use App\Constant\CategoryConstant;
use App\Models\Period\PeriodModel;
use App\Constant\LineSourceConstant;
use App\Models\Installment\CardModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a line attribute set for a period.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function line(PeriodModel $period, array $extra = []): array
    {
        return $extra + ['period_id' => $period->id, 'category' => CategoryConstant::GASTO, 'name' => 'x', 'estimated' => '1.00'];
    }

    /**
     * A period is unique per (year, month, half).
     */
    public function test_duplicate_period_is_rejected(): void
    {
        PeriodModel::create(['year' => 2026, 'month' => 10, 'half' => 1]);

        $this->expectException(QueryException::class);
        PeriodModel::create(['year' => 2026, 'month' => 10, 'half' => 1]);
    }

    /**
     * Only one installments line per (period, card), but other sources may repeat.
     */
    public function test_installments_line_is_unique_per_period_and_card(): void
    {
        $period = PeriodModel::create(['year' => 2026, 'month' => 10, 'half' => 1]);
        $card = CardModel::create(['name' => 'Visa']);
        $cuotas = $this->line($period, ['source' => LineSourceConstant::INSTALLMENTS, 'card_id' => $card->id]);

        LineModel::create($cuotas);
        LineModel::create($this->line($period, ['card_id' => $card->id]));
        LineModel::create($this->line($period, ['card_id' => $card->id]));
        $this->assertSame(3, LineModel::count());

        $this->expectException(QueryException::class);
        LineModel::create($cuotas);
    }

    /**
     * Money survives a DB round trip exactly.
     */
    public function test_money_round_trip_is_exact(): void
    {
        $period = PeriodModel::create(['year' => 2026, 'month' => 10, 'half' => 1]);
        $line = LineModel::create($this->line($period, ['estimated' => MoneyHelper::fromCents(1086500)]));

        $estimated = $line->fresh()->estimated;
        $this->assertIsString($estimated);
        $this->assertSame('10865.00', $estimated);
        $this->assertSame(1086500, MoneyHelper::toCents($estimated));
    }

    /**
     * Deleting a period cascades to its lines.
     */
    public function test_deleting_period_cascades_lines(): void
    {
        $period = PeriodModel::create(['year' => 2026, 'month' => 10, 'half' => 1]);
        LineModel::create($this->line($period));

        $period->delete();

        $this->assertSame(0, LineModel::count());
    }
}
