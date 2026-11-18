<?php

namespace Tests\Feature\Period;

use App\Helpers\MoneyHelper;
use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
use App\Models\Period\PeriodModel;
use Illuminate\Testing\TestResponse;
use App\Models\Installment\CardModel;
use App\Models\Template\TemplateModel;

class FlexiblePeriodTest extends PeriodTestCase
{
    /**
     * Create a space directly in the database (Space CRUD comes later).
     *
     * @param  string  $frequency  The frequency.
     * @param  int|null  $customDays  The custom length.
     * @return SpaceModel The space.
     */
    private function space(string $frequency, ?int $customDays = null): SpaceModel
    {
        return SpaceModel::create(['Name' => 'Espacio '.$frequency.$customDays, 'Frequency' => $frequency, 'CustomDays' => $customDays]);
    }

    /**
     * Store a period of a space through the endpoint.
     *
     * @return TestResponse The response.
     */
    private function store(SpaceModel $space, string $start, string $end)
    {
        return $this->postJson('/periods/store', ['spaceId' => $space->IdSpace, 'startDate' => $start, 'endDate' => $end]);
    }

    /**
     * Weekly, monthly and custom spaces create periods with their frequency in the JSON; next follows the adjusted dates.
     */
    public function test_creates_periods_for_any_frequency_and_next_follows_adjusted_dates(): void
    {
        $weekly = $this->space('weekly');
        $p = $this->store($weekly, '2026-11-02', '2026-11-08')->assertCreated()->assertJsonPath('period.frequency', 'weekly')->json('period');
        $this->assertSame('Semana 2–8 nov 2026', $p['label']);
        $this->store($this->space('monthly'), '2026-11-01', '2026-11-30')->assertCreated()->assertJsonPath('period.label', 'Noviembre 2026');
        $this->store($this->space('custom', 10), '2026-11-01', '2026-11-10')->assertCreated()->assertJsonPath('period.frequency', 'custom');

        $this->putJson("/periods/{$p['id']}/update", ['startDate' => '2026-11-02', 'endDate' => '2026-11-10'])->assertOk()->assertJsonPath('period.endDate', '2026-11-10');
        $this->getJson('/periods/next-proposal?space='.$weekly->IdSpace)->assertOk()->assertExactJson(['startDate' => '2026-11-11', 'endDate' => '2026-11-17', 'label' => 'Semana 11–17 nov 2026']);
        $this->postJson('/periods/next', ['spaceId' => $weekly->IdSpace])->assertCreated()->assertJsonPath('period.startDate', '2026-11-11');
        $this->getJson("/periods/{$p['id']}/data")->assertJsonPath('period.frequency', 'weekly');
    }

    /**
     * Overlaps are 422 on startDate (also same start with another end), end before start is 422 on endDate, gaps are fine.
     */
    public function test_overlap_and_date_rules(): void
    {
        $space = $this->space('weekly');
        $this->store($space, '2026-11-02', '2026-11-08')->assertCreated();
        $this->store($space, '2026-11-02', '2026-11-08')->assertCreated();
        $this->store($space, '2026-11-02', '2026-11-09')->assertStatus(422)->assertJsonValidationErrors('startDate')->assertJsonPath('errors.startDate.0', 'Las fechas se traslapan con otro periodo');
        $this->store($space, '2026-11-08', '2026-11-14')->assertStatus(422)->assertJsonValidationErrors('startDate');
        $this->store($space, '2026-10-30', '2026-11-03')->assertStatus(422);
        $this->store($space, '2026-11-20', '2026-11-19')->assertStatus(422)->assertJsonValidationErrors('endDate');
        $this->store($space, '2026-11-20', '2026-11-26')->assertCreated();
        $this->assertSame(2, PeriodModel::where('FkSpace', $space->IdSpace)->count());

        $other = $this->space('monthly');
        $this->store($other, '2026-11-02', '2026-11-08')->assertCreated();
    }

    /**
     * Updating dates: overlap 422, end before start 422, reordering 409, a harmless change keeps the lines.
     */
    public function test_update_rules(): void
    {
        $space = $this->space('weekly');
        [$a, $b, $c] = array_map(fn ($d) => $this->store($space, $d[0], $d[1])->assertCreated()->json('period.id'), [['2026-11-02', '2026-11-08'], ['2026-11-09', '2026-11-15'], ['2026-11-16', '2026-11-22']]);

        $this->putJson("/periods/$a/update", ['startDate' => '2026-11-02', 'endDate' => '2026-11-09'])->assertStatus(422)->assertJsonValidationErrors('startDate');
        $this->putJson("/periods/$a/update", ['startDate' => '2026-11-05', 'endDate' => '2026-11-04'])->assertStatus(422)->assertJsonValidationErrors('endDate');
        $this->putJson("/periods/$a/update", ['startDate' => '2026-11-23', 'endDate' => '2026-11-29'])->assertStatus(409)->assertJsonPath('message', 'El cambio de fechas cambiaría el orden de los periodos');
        $this->putJson("/periods/$c/update", ['startDate' => '2026-10-20', 'endDate' => '2026-10-26'])->assertStatus(409);
        $this->putJson('/periods/9999/update', ['startDate' => '2026-11-23', 'endDate' => '2026-11-29'])->assertNotFound();
        $this->putJson("/periods/$b/update", ['startDate' => '2026-11-10', 'endDate' => '2026-11-12'])->assertOk()->assertJsonPath('period.startDate', '2026-11-10');
        $this->assertSame('2026-11-12', PeriodModel::find($b)->EndDate);
    }

    /**
     * Monthly templates across a weekly month with 5 periods: repartido adds up, mensual_dia lands once.
     */
    public function test_templates_across_a_weekly_month_with_five_periods(): void
    {
        $space = $this->space('weekly');
        foreach ([['Banco', 24000001, 'mensual_repartido', null], ['Renta', 5000000, 'mensual_dia', 15]] as [$name, $cents, $frequency, $day]) {
            TemplateModel::create(['FkSpace' => $space->IdSpace, 'Name' => $name, 'Category' => 'deuda_fija', 'Estimated' => MoneyHelper::fromCents($cents), 'Frequency' => $frequency, 'DayOfMonth' => $day, 'Active' => true]);
        }
        $ids = [];
        foreach ([2, 9, 16, 23, 30] as $d) {
            $ids[] = $this->store($space, sprintf('2026-11-%02d', $d), date('Y-m-d', strtotime(sprintf('2026-11-%02d +6 days', $d))))->assertCreated()->json('period.id');
        }

        $banco = LineModel::whereIn('FkPeriod', $ids)->where('Name', 'Banco')->orderBy('FkPeriod')->pluck('Estimated')->map(fn ($e) => MoneyHelper::requireCents($e));
        $this->assertCount(5, $banco);
        $this->assertSame(24000001, $banco->sum());
        $this->assertSame([4800001, 4800000, 4800000, 4800000, 4800000], $banco->all());
        $this->assertSame([$ids[1]], LineModel::whereIn('FkPeriod', $ids)->where('Name', 'Renta')->pluck('FkPeriod')->all());
    }

    /**
     * Cuotas split across 4 weekly periods adds up to the monthly cuota.
     */
    public function test_cuotas_split_across_four_weekly_periods(): void
    {
        $space = $this->space('weekly');
        $card = CardModel::create(['FkSpace' => $space->IdSpace, 'Name' => 'TC Semanal']);
        $this->seedInstallment($card, 'Reloj', 'tasa0', 24, 12, 1104261 * 24, null, '2027-01');
        foreach (['2027-02-01', '2027-02-08', '2027-02-15', '2027-02-22'] as $start) {
            $this->store($space, $start, date('Y-m-d', strtotime("$start +6 days")))->assertCreated();
        }

        $amounts = LineModel::where(['Source' => 'installments', 'FkCard' => $card->IdCard])->orderBy('FkPeriod')->pluck('Estimated')->map(fn ($e) => MoneyHelper::requireCents($e));
        $this->assertCount(4, $amounts);
        $this->assertSame(1104261, $amounts->sum());
    }

    /**
     * A non-aligned biweekly month with 3 starts keeps the whole monthly amount.
     */
    public function test_non_aligned_biweekly_month_keeps_the_whole_amount(): void
    {
        $space = $this->space('biweekly');
        TemplateModel::create(['FkSpace' => $space->IdSpace, 'Name' => 'Alquiler', 'Category' => 'servicio', 'Estimated' => '1000.00', 'Frequency' => 'mensual_repartido', 'Active' => true]);
        $ids = [];
        foreach ([['2026-03-03', '2026-03-16'], ['2026-03-17', '2026-03-30'], ['2026-03-31', '2026-04-13']] as [$s, $e]) {
            $ids[] = $this->store($space, $s, $e)->assertCreated()->json('period.id');
        }

        $sum = LineModel::whereIn('FkPeriod', $ids)->where('Name', 'Alquiler')->pluck('Estimated')->sum(fn ($e) => MoneyHelper::requireCents($e));
        $this->assertSame(100000, $sum);
    }

    /**
     * A bad, missing or deleted space is a 404 JSON everywhere it is read; omitted means the primary space.
     */
    public function test_space_param_validation(): void
    {
        $deleted = $this->space('weekly');
        $deleted->logicalDelete();

        foreach (['abc', '0', '-1', '1.5', '99999', (string) $deleted->IdSpace] as $bad) {
            $this->getJson('/periods?space='.$bad)->assertNotFound();
            $this->getJson('/dashboard/data?space='.$bad)->assertNotFound();
            $this->getJson('/periods/next-proposal?space='.$bad)->assertNotFound();
            $this->postJson('/periods/next', ['spaceId' => $bad])->assertNotFound();
        }
        $this->getJson('/dashboard/data')->assertOk();
        $this->getJson('/dashboard/data?space=')->assertOk();
        $this->getJson('/periods/next-proposal')->assertOk()->assertJsonStructure(['startDate', 'endDate', 'label']);
        $this->postJson('/periods/store', ['spaceId' => 99999] + $this->dates(11, 1))->assertNotFound();
    }
}
