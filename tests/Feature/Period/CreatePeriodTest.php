<?php

namespace Tests\Feature\Period;

use App\Models\Period\LineModel;
use App\Models\Period\PeriodModel;
use App\Models\Installment\InstallmentModel;

class CreatePeriodTest extends PeriodTestCase
{
    /**
     * Templates expand per half with the monthly split and source template.
     */
    public function test_expands_templates_per_half(): void
    {
        $this->seedTemplates();
        $p1 = $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 1]);
        $p2 = $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 2]);

        $h1 = LineModel::where('period_id', $p1['id'])->get()->map(fn ($l) => [$l->name, $l->estimated, $l->source, $l->paid])->sort()->values()->all();
        $this->assertSame([['Banco Popular', '120000.00', 'template', false], ['CONAPE', '68460.00', 'template', false], ['Kolbi', '10865.00', 'template', false]], $h1);
        $this->assertSame(['Banco Popular', 'Kolbi'], LineModel::where('period_id', $p2['id'])->orderBy('name')->pluck('name')->all());
        $this->assertSame('120000.00', LineModel::where(['period_id' => $p2['id'], 'name' => 'Banco Popular'])->value('estimated'));
    }

    /**
     * Creating an existing key returns the same period and adds no lines.
     */
    public function test_is_idempotent(): void
    {
        $this->seedTemplates();
        $a = $this->makePeriod();
        $b = $this->postJson('/periods/store', ['year' => 2026, 'month' => 11, 'half' => 1])->assertCreated()->json('period');

        $this->assertSame($a['id'], $b['id']);
        $this->assertSame('I Quincena Noviembre', $a['label']);
        $this->assertSame(1, PeriodModel::count());
        $this->assertSame(3, LineModel::count());
    }

    /**
     * Installments advance only on the first half of a new month.
     */
    public function test_advances_installments_only_on_half_one_of_a_new_month(): void
    {
        $this->seedReloj();
        $state = fn () => InstallmentModel::first()->only(['paid_installments', 'last_advanced_month']);

        $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 1]);
        $this->assertSame(['paid_installments' => 12, 'last_advanced_month' => '2026-10'], $state());
        $this->makePeriod(['year' => 2026, 'month' => 12, 'half' => 1]);
        $this->assertSame(['paid_installments' => 13, 'last_advanced_month' => '2026-11'], $state());
        $this->makePeriod(['year' => 2026, 'month' => 12, 'half' => 2]);
        $this->assertSame(13, $state()['paid_installments']);
    }

    /**
     * Creating a period generates the cuotas line with actual null.
     */
    public function test_creates_the_cuotas_line(): void
    {
        $card = $this->seedReloj();
        $p = $this->makePeriod();

        $line = $this->cuotasLines($p['id'])->sole();
        $this->assertSame(['Cuotas TC BAC', '5521.31', null, 'deuda_fija', $card->id], [$line->name, $line->estimated, $line->actual, $line->category, $line->card_id]);
    }

    /**
     * POST next follows the latest period, wraps the year and rejects when empty.
     */
    public function test_next_follows_latest_or_rejects(): void
    {
        $this->postJson('/periods/next')->assertStatus(409)->assertJsonPath('message', 'No hay quincenas para continuar.');

        $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 1]);
        $this->postJson('/periods/next')->assertCreated()->assertJsonPath('period.half', 2)->assertJsonPath('period.month', 11);

        $this->makePeriod(['year' => 2026, 'month' => 12, 'half' => 2]);
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

        $keys = PeriodModel::get()->map(fn ($p) => "$p->year-$p->month-$p->half");
        $this->assertSame($keys->count(), $keys->unique()->count());
    }

    /**
     * Invalid keys give 422 with errors per field.
     */
    public function test_rejects_invalid_key(): void
    {
        $this->postJson('/periods/store', ['year' => 1999, 'month' => 13, 'half' => 3])
            ->assertStatus(422)->assertJsonValidationErrors(['year', 'month', 'half']);
    }
}
