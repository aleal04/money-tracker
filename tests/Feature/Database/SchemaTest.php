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
use App\Models\Installment\InstallmentModel;

class SchemaTest extends TestCase
{
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

    /**
     * Deleting a card nulls the card reference on its lines.
     */
    public function test_deleting_card_nulls_line_card(): void
    {
        $period = PeriodModel::create(['year' => 2026, 'month' => 10, 'half' => 1]);
        $card = CardModel::create(['name' => 'Visa']);
        $line = LineModel::create($this->line($period, ['card_id' => $card->id]));

        $card->delete();

        $this->assertNull($line->fresh()->card_id);
    }

    /**
     * A card with installments cannot be deleted.
     */
    public function test_card_with_installments_cannot_be_deleted(): void
    {
        $card = CardModel::create(['name' => 'Visa']);
        InstallmentModel::create([
            'card_id' => $card->id, 'merchant' => 'm', 'item' => 'i', 'total_installments' => 6,
            'type' => 'tasa0', 'initial_balance' => '600.00', 'last_advanced_month' => '2026-10',
        ]);

        $this->expectException(QueryException::class);
        $card->delete();
    }

    /**
     * Deleting a carried-to line nulls carried_to_line_id on the lines pointing at it.
     */
    public function test_deleting_carried_to_line_nulls_reference(): void
    {
        $period = PeriodModel::create(['year' => 2026, 'month' => 10, 'half' => 1]);
        $target = LineModel::create($this->line($period));
        $source = LineModel::create($this->line($period, ['carried_to_line_id' => $target->id]));

        $target->delete();

        $this->assertNull($source->fresh()->carried_to_line_id);
    }
}
