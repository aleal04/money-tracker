<?php

namespace Tests\Feature\Period;

use App\Models\Period\LineModel;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

class PeriodLineTest extends PeriodTestCase
{
    /**
     * Add a manual line through HTTP.
     *
     * @param  int  $periodId  The period identifier.
     * @param  array<string, mixed>  $line  The line fields.
     * @return TestResponse The response.
     */
    private function addLine(int $periodId, array $line)
    {
        return $this->postJson("/periods/$periodId/lines/store", $line);
    }

    /**
     * Index redirects to the latest period, or renders the create form when empty; show renders the page.
     */
    public function test_index_and_show_pages(): void
    {
        $this->get('/periods')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Period/Index', false));
        $p = $this->makePeriod();
        $this->get('/periods')->assertRedirect("/periods/{$p['id']}");
        $this->get("/periods/{$p['id']}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Period/Show', false)->where('periodId', $p['id']));
        $this->get('/periods/999')->assertNotFound();
    }

    /**
     * Amounts are parsed from es-CR input.
     */
    public function test_parses_amounts(): void
    {
        $p = $this->makePeriod();
        $this->assertSame('I Quincena Noviembre', $this->getJson("/periods/{$p['id']}/data")->json('period.label'));
        $this->assertNull($this->getJson("/periods/{$p['id']}/data")->json('prevId'));

        foreach (['5.900' => 590000, '₡ 10.865' => 1086500, '5.900,50' => 590050] as $input => $cents) {
            $this->addLine($p['id'], ['category' => 'gasto', 'name' => 'x', 'estimated' => (string) $input])
                ->assertCreated()->assertJsonPath('line.estimated', $cents);
        }
        $this->assertSame(['5900.00', '10865.00', '5900.50'], LineModel::orderBy('id')->pluck('estimated')->all());
    }

    /**
     * Bad amounts and fields give 422 with errors per field.
     */
    public function test_rejects_bad_input(): void
    {
        $p = $this->makePeriod();
        $this->addLine($p['id'], ['category' => 'gasto', 'name' => 'x', 'estimated' => 'abc'])
            ->assertStatus(422)->assertJsonPath('errors.estimated.0', 'Monto inválido');
        $this->addLine($p['id'], ['category' => 'nope', 'name' => 'x', 'estimated' => '1'])->assertJsonValidationErrors('category');
        $this->addLine($p['id'], ['category' => 'gasto', 'name' => '  ', 'estimated' => '1'])->assertJsonValidationErrors('name');
        $this->addLine($p['id'], ['category' => 'gasto', 'name' => 'x', 'estimated' => '1', 'actual' => 'zz'])->assertJsonValidationErrors('actual');
        $this->addLine(999, ['category' => 'gasto', 'name' => 'x', 'estimated' => '1'])->assertNotFound();
    }

    /**
     * The summary of a small quincena.
     */
    public function test_summarizes_a_quincena(): void
    {
        $p = $this->makePeriod();
        $this->addLine($p['id'], ['category' => 'ingreso', 'name' => 'Salario', 'estimated' => '1.000.000'])->assertCreated();
        $this->addLine($p['id'], ['category' => 'servicio', 'name' => 'Luz', 'estimated' => '300.000'])->assertCreated();
        $this->addLine($p['id'], ['category' => 'gasto', 'name' => 'Super', 'estimated' => '100.000', 'paid' => true])->assertCreated();

        $this->getJson("/periods/{$p['id']}/data")->assertJsonPath('summary.ingreso', 100000000)
            ->assertJsonPath('summary.egreso', 40000000)->assertJsonPath('summary.disponible', 60000000)
            ->assertJsonPath('summary.faltaPorPagar', 30000000)->assertJsonPath('summary.byCategory.gasto.estimated', 10000000);
    }

    /**
     * The Excel I Quincena Octubre totals.
     */
    public function test_summarizes_the_excel_quincena(): void
    {
        $p = $this->makePeriod(['year' => 2026, 'month' => 10, 'half' => 1]);
        $rows = [
            ['ingreso', 557632, true],
            ['servicio', 10865, true], ['servicio', 16146, true], ['servicio', 68460, true], ['servicio', 45000, true],
            ['ahorro', 32580, true],
            ['deuda_fija', 120000, true], ['deuda_fija', 100013, true], ['deuda_fija', 69132, true],
            ['gasto', 5900, false], ['gasto', 15000, false], ['gasto', 10000, false], ['gasto', 0, false],
        ];
        foreach ($rows as [$category, $amount, $paid]) {
            $this->addLine($p['id'], ['category' => $category, 'name' => 'x', 'estimated' => (string) $amount, 'paid' => $paid])->assertCreated();
        }

        $this->getJson("/periods/{$p['id']}/data")->assertJsonPath('summary.ingreso', 55763200)->assertJsonPath('summary.egreso', 49309600)
            ->assertJsonPath('summary.disponible', 6453600)->assertJsonPath('summary.faltaPorPagar', 3090000);
    }

    /**
     * Prev and next ids follow chronological order.
     */
    public function test_prev_and_next_ids(): void
    {
        $a = $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 1]);
        $b = $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 2]);

        $this->getJson("/periods/{$a['id']}/data")->assertJsonPath('prevId', null)->assertJsonPath('nextId', $b['id']);
        $this->getJson("/periods/{$b['id']}/data")->assertJsonPath('prevId', $a['id'])->assertJsonPath('nextId', null);
        $this->getJson('/periods/999/data')->assertNotFound();
    }

    /**
     * Update and delete a line; trimmed name, partial fields, 404 afterwards.
     */
    public function test_updates_and_deletes_a_line(): void
    {
        $p = $this->makePeriod();
        $id = $this->addLine($p['id'], ['category' => 'gasto', 'name' => 'a', 'estimated' => '1'])->json('line.id');

        $this->putJson("/lines/$id/update", ['actual' => '2', 'name' => ' b '])->assertOk()
            ->assertJsonPath('line.name', 'b')->assertJsonPath('line.actual', 200)->assertJsonPath('line.estimated', 100);
        $this->putJson("/lines/$id/update", ['actual' => null])->assertOk()->assertJsonPath('line.actual', null);
        $this->deleteJson("/lines/$id/delete")->assertOk();
        $this->putJson("/lines/$id/update", ['name' => 'c'])->assertNotFound();
    }

    /**
     * Toggling paid sets and clears paidAt.
     */
    public function test_toggle_paid(): void
    {
        $p = $this->makePeriod();
        $id = $this->addLine($p['id'], ['category' => 'gasto', 'name' => 'a', 'estimated' => '1'])->json('line.id');

        $this->assertNotNull($this->postJson("/lines/$id/toggle-paid")->assertOk()->assertJsonPath('line.paid', true)->json('line.paidAt'));
        $this->postJson("/lines/$id/toggle-paid")->assertOk()->assertJsonPath('line.paid', false)->assertJsonPath('line.paidAt', null);
    }

    /**
     * A carried line is immutable, an arrastre line cannot be deleted and lists its originals.
     */
    public function test_carried_lines_are_immutable_and_arrastre_is_protected(): void
    {
        $p = $this->makePeriod();
        $next = $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 2]);
        $original = $this->addLine($p['id'], ['category' => 'gasto', 'name' => 'Deuda', 'estimated' => '5.000'])->json('line.id');
        $arrastre = LineModel::create(['period_id' => $next['id'], 'category' => 'gasto', 'name' => 'Arrastre', 'estimated' => '5000.00', 'source' => 'carried']);
        LineModel::whereKey($original)->update(['carried_to_line_id' => $arrastre->id]);

        $this->putJson("/lines/$original/update", ['name' => 'z'])->assertStatus(409)->assertJsonPath('message', 'Línea arrastrada');
        $this->postJson("/lines/$original/toggle-paid")->assertStatus(409);
        $this->deleteJson("/lines/$original/delete")->assertStatus(409);
        $this->deleteJson("/lines/{$arrastre->id}/delete")->assertStatus(409)->assertJsonPath('message', 'Para quitar el arrastre, reabrí la quincena anterior');

        $this->getJson("/lines/{$arrastre->id}/arrastre-detail")->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.id', $original)->assertJsonPath('0.carried', true);
        $this->getJson("/periods/{$p['id']}/data")->assertJsonPath('lines.0.carried', true)->assertJsonPath('summary.egreso', 0);
        $this->postJson("/lines/{$arrastre->id}/toggle-paid")->assertOk()->assertJsonPath('line.paid', true);
    }

    /**
     * A line in a closed period can be paid but not un-paid.
     */
    public function test_cannot_unpay_in_a_closed_period(): void
    {
        $p = $this->makePeriod();
        $this->period(11, 1)->update(['closed' => true]);
        $id = $this->addLine($p['id'], ['category' => 'gasto', 'name' => 'Tarde', 'estimated' => '1.000'])->assertCreated()->json('line.id');

        $this->postJson("/lines/$id/toggle-paid")->assertOk()->assertJsonPath('line.paid', true);
        $this->postJson("/lines/$id/toggle-paid")->assertStatus(409)->assertJsonPath('message', 'No se puede desmarcar un pago en una quincena cerrada');
        $this->putJson("/lines/$id/update", ['paid' => false])->assertStatus(409);
        $this->putJson("/lines/$id/update", ['name' => 'ok'])->assertOk();
        $this->assertTrue((bool) LineModel::find($id)->paid);
    }
}
