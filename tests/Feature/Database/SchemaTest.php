<?php

namespace Tests\Feature\Database;

use Tests\TestCase;
use App\Helpers\MoneyHelper;
use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
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
        return $extra + ['FkPeriod' => $period->IdPeriod, 'Category' => CategoryConstant::GASTO, 'Name' => 'x', 'Estimated' => '1.00'];
    }

    /**
     * A live period is unique per (space, start date); a deleted one frees the slot.
     */
    public function test_duplicate_period_is_rejected(): void
    {
        PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15'])->logicalDelete();
        PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15']);

        $this->expectException(QueryException::class);
        PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15']);
    }

    /**
     * Only one live primary space.
     */
    public function test_second_primary_space_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        SpaceModel::create(['Name' => 'Otro', 'Frequency' => 'monthly', 'IsPrimary' => true]);
    }

    /**
     * Only one installments line per (period, card), but other sources may repeat.
     */
    public function test_installments_line_is_unique_per_period_and_card(): void
    {
        $period = PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15']);
        $card = CardModel::create(['FkSpace' => $this->primarySpaceId(), 'Name' => 'Visa']);
        $cuotas = $this->line($period, ['Source' => LineSourceConstant::INSTALLMENTS, 'FkCard' => $card->IdCard]);

        LineModel::create($cuotas);
        LineModel::create($this->line($period, ['FkCard' => $card->IdCard]));
        LineModel::create($this->line($period, ['FkCard' => $card->IdCard]));
        $this->assertSame(3, LineModel::count());

        $this->expectException(QueryException::class);
        LineModel::create($cuotas);
    }

    /**
     * A logically deleted installments line does not block a new one for the same (period, card).
     */
    public function test_deleted_installments_line_frees_the_unique_slot(): void
    {
        $period = PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15']);
        $card = CardModel::create(['FkSpace' => $this->primarySpaceId(), 'Name' => 'Visa']);
        $cuotas = $this->line($period, ['Source' => LineSourceConstant::INSTALLMENTS, 'FkCard' => $card->IdCard]);

        LineModel::create($cuotas)->logicalDelete();
        LineModel::create($cuotas);

        $this->assertSame(1, LineModel::count());
        $this->assertSame(2, LineModel::withoutGlobalScopes()->count());
    }

    /**
     * Money survives a DB round trip exactly.
     */
    public function test_money_round_trip_is_exact(): void
    {
        $period = PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15']);
        $line = LineModel::create($this->line($period, ['Estimated' => MoneyHelper::fromCents(1086500)]));

        $estimated = $line->fresh()->Estimated;
        $this->assertIsString($estimated);
        $this->assertSame('10865.00', $estimated);
        $this->assertSame(1086500, MoneyHelper::toCents($estimated));
    }

    /**
     * Deleting a period cascades to its lines.
     */
    public function test_deleting_period_cascades_lines(): void
    {
        $period = PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15']);
        LineModel::create($this->line($period));

        $period->delete();

        $this->assertSame(0, LineModel::count());
    }

    /**
     * Deleting a card nulls the card reference on its lines.
     */
    public function test_deleting_card_nulls_line_card(): void
    {
        $period = PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15']);
        $card = CardModel::create(['FkSpace' => $this->primarySpaceId(), 'Name' => 'Visa']);
        $line = LineModel::create($this->line($period, ['FkCard' => $card->IdCard]));

        $card->delete();

        $this->assertNull($line->fresh()->FkCard);
    }

    /**
     * A card with installments cannot be deleted.
     */
    public function test_card_with_installments_cannot_be_deleted(): void
    {
        $card = CardModel::create(['FkSpace' => $this->primarySpaceId(), 'Name' => 'Visa']);
        InstallmentModel::create([
            'FkCard' => $card->IdCard, 'Merchant' => 'm', 'Item' => 'i', 'TotalInstallments' => 6,
            'Type' => 'tasa0', 'InitialBalance' => '600.00', 'LastAdvancedMonth' => '2026-10',
        ]);

        $this->expectException(QueryException::class);
        $card->delete();
    }

    /**
     * Deleting a carried-to line nulls carried_to_line_id on the lines pointing at it.
     */
    public function test_deleting_carried_to_line_nulls_reference(): void
    {
        $period = PeriodModel::create(['FkSpace' => $this->primarySpaceId(), 'StartDate' => '2026-10-01', 'EndDate' => '2026-10-15']);
        $target = LineModel::create($this->line($period));
        $source = LineModel::create($this->line($period, ['FkCarriedToLine' => $target->IdLine]));

        $target->delete();

        $this->assertNull($source->fresh()->FkCarriedToLine);
    }
}
