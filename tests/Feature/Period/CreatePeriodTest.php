<?php

namespace Tests\Feature\Period;

use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
use App\Models\Period\PeriodModel;
use App\Models\Installment\CardModel;
use App\Models\Installment\InstallmentModel;

class CreatePeriodTest extends PeriodTestCase
{
    /**
     * Templates expand per half with the monthly split and source template.
     */
    public function test_expands_templates_per_half(): void
    {
        $this->seedTemplates();
        $p1 = $this->makePeriod($this->dates(11, 1));
        $p2 = $this->makePeriod($this->dates(11, 2));

        $h1 = LineModel::where('FkPeriod', $p1['id'])->get()->map(fn ($l) => [$l->Name, $l->Estimated, $l->Source, $l->Paid])->sort()->values()->all();
        $this->assertSame([['Banco Popular', '120000.00', 'template', false], ['CONAPE', '68460.00', 'template', false], ['Kolbi', '10865.00', 'template', false]], $h1);
        $this->assertSame(['Banco Popular', 'Kolbi'], LineModel::where('FkPeriod', $p2['id'])->orderBy('Name')->pluck('Name')->all());
        $this->assertSame('120000.00', LineModel::where(['FkPeriod' => $p2['id'], 'Name' => 'Banco Popular'])->value('Estimated'));
    }

    /**
     * Creating an existing start date returns the same period and adds no lines; the view has dates and the derived key.
     */
    public function test_is_idempotent(): void
    {
        $this->seedTemplates();
        $a = $this->makePeriod();
        $b = $this->postJson('/periods/store', $this->dates(11, 1))->assertCreated()->json('period');

        $this->assertSame($a['id'], $b['id']);
        $this->assertSame('I Quincena Noviembre 2026', $a['label']);
        $this->assertSame(['startDate' => '2026-11-01', 'endDate' => '2026-11-15', 'year' => 2026, 'month' => 11, 'half' => 1], array_intersect_key($a, array_flip(['startDate', 'endDate', 'year', 'month', 'half'])));
        $this->assertSame(1, PeriodModel::count());
        $this->assertSame(3, LineModel::count());
    }

    /**
     * Installments advance only on the first period of a new month in the space.
     */
    public function test_advances_installments_only_on_first_period_of_a_new_month(): void
    {
        $this->seedReloj();
        $other = SpaceModel::create(['Name' => 'Ahorro', 'Frequency' => 'monthly']);
        $otherCard = CardModel::create(['FkSpace' => $other->IdSpace, 'Name' => 'Otra']);
        $this->seedInstallment($otherCard, 'Tele', 'tasa0', 10, 2, 100000, null);
        $state = fn () => InstallmentModel::first()->only(['PaidInstallments', 'LastAdvancedMonth']);

        $this->makePeriod($this->dates(11, 1));
        $this->assertSame(['PaidInstallments' => 12, 'LastAdvancedMonth' => '2026-10'], $state());
        $this->makePeriod($this->dates(12, 1));
        $this->assertSame(['PaidInstallments' => 13, 'LastAdvancedMonth' => '2026-11'], $state());
        $this->makePeriod($this->dates(12, 2));
        $this->assertSame(13, $state()['PaidInstallments']);
        $this->makePeriod($this->dates(1, 2, 2027)); // first period of January, even if it is the II quincena
        $this->assertSame(['PaidInstallments' => 14, 'LastAdvancedMonth' => '2026-12'], $state());
        $this->assertSame(2, InstallmentModel::where('FkCard', $otherCard->IdCard)->value('PaidInstallments'));
    }

    /**
     * Creating a period generates the cuotas line with actual null.
     */
    public function test_creates_the_cuotas_line(): void
    {
        $card = $this->seedReloj();
        $p = $this->makePeriod();

        $line = $this->cuotasLines($p['id'])->sole();
        $this->assertSame(['Cuotas TC BAC', '5521.31', null, 'deuda_fija', $card->IdCard], [$line->Name, $line->Estimated, $line->Actual, $line->Category, $line->FkCard]);
    }

    /**
     * POST next follows the latest period, wraps the year and rejects when empty.
     */
    public function test_next_follows_latest_or_rejects(): void
    {
        $this->postJson('/periods/next')->assertStatus(409)->assertJsonPath('message', 'No hay quincenas para continuar.');

        $this->makePeriod($this->dates(11, 1));
        $this->postJson('/periods/next')->assertCreated()->assertJsonPath('period.half', 2)->assertJsonPath('period.month', 11);

        $this->makePeriod($this->dates(12, 2));
        $this->postJson('/periods/next')->assertCreated()->assertJsonPath('period.year', 2027)->assertJsonPath('period.month', 1)->assertJsonPath('period.half', 1);
    }

    /**
     * Repeated next calls never duplicate a key.
     */
    public function test_double_next_never_duplicates_keys(): void
    {
        $this->makePeriod();
        $this->postJson('/periods/next')->assertCreated();
        $this->postJson('/periods/next')->assertCreated();

        $keys = PeriodModel::get()->map(fn ($p) => "$p->FkSpace-$p->StartDate");
        $this->assertSame($keys->count(), $keys->unique()->count());
    }

    /**
     * Invalid dates give 422 with errors per field; an unknown space gives 404.
     */
    public function test_rejects_invalid_dates(): void
    {
        $this->postJson('/periods/store', ['startDate' => '2026-13-01', 'endDate' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['startDate', 'endDate']);
        $this->postJson('/periods/store', ['startDate' => '2026-11-15', 'endDate' => '2026-11-01'])
            ->assertStatus(422)->assertJsonValidationErrors(['endDate' => 'El fin debe ser igual o posterior al inicio']);
        $this->postJson('/periods/store', ['spaceId' => 999] + $this->dates(11, 1))->assertNotFound();
        $this->postJson('/periods/next', ['spaceId' => 999])->assertNotFound();
    }
}
