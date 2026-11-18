<?php

namespace Tests\Feature\Period;

use App\Models\Period\LineModel;
use Illuminate\Support\Collection;
use App\Repositories\Period\LineQueryRepository;

class ClosePeriodTest extends PeriodTestCase
{
    /**
     * Add a line through the HTTP endpoint and return its view.
     *
     * @param  int  $periodId  The period identifier.
     * @param  array<string, mixed>  $line  The line fields (cents as strings).
     * @return array<string, mixed> The line view.
     */
    private function addLine(int $periodId, array $line): array
    {
        return $this->postJson("/periods/{$periodId}/lines/store", $line + ['category' => 'gasto'])->assertCreated()->json('line');
    }

    /**
     * The arrastre lines of a period key.
     *
     * @param  int  $month  The month.
     * @param  int  $half  The half.
     * @return Collection<int, LineModel> The arrastre lines.
     */
    private function arrastres(int $month = 11, int $half = 2)
    {
        return LineModel::where(['period_id' => $this->period($month, $half)->id, 'source' => 'carried'])->get();
    }

    /**
     * Start in the I Quincena Octubre like the NestJS tests.
     *
     * @return array<string, mixed> The period view.
     */
    private function october(): array
    {
        return $this->makePeriod(['year' => 2026, 'month' => 10, 'half' => 1]);
    }

    public function test_carries_unpaid_egresos_into_one_arrastre_line(): void
    {
        $p = $this->october();
        $this->addLine($p['id'], ['category' => 'ingreso', 'name' => 'Sueldo', 'estimated' => '50.000']);
        $a = $this->addLine($p['id'], ['name' => 'Super', 'estimated' => '5.900', 'actual' => '5.900']);
        $b = $this->addLine($p['id'], ['name' => 'Casa', 'estimated' => '15.000']);
        $c = $this->addLine($p['id'], ['name' => 'Otro', 'estimated' => '10.000']);
        $this->addLine($p['id'], ['category' => 'deuda_fija', 'name' => 'Kolbi', 'estimated' => '10.865', 'paid' => true]);

        $preview = $this->getJson("/periods/{$p['id']}/close-preview")->assertOk()->json();
        $this->assertSame(3090000, $preview['total']);
        $this->assertSame([$a['id'], $b['id'], $c['id']], array_column($preview['lines'], 'id'));

        $arrastreId = $this->postJson("/periods/{$p['id']}/close")->assertCreated()->json('arrastreLineId');

        $arrastre = $this->arrastres(10, 2);
        $this->assertCount(1, $arrastre);
        $this->assertSame($arrastreId, $arrastre[0]->id);
        $this->assertSame(['Arrastre I Quincena Octubre', 'deuda_fija', false], [$arrastre[0]->name, $arrastre[0]->category, $arrastre[0]->paid]);
        $this->assertSame([3090000, 3090000], [$arrastre[0]->toView()['estimated'], $arrastre[0]->toView()['actual']]);
        $this->assertSame([$a['id'], $b['id'], $c['id']], LineModel::where('carried_to_line_id', $arrastreId)->orderBy('id')->pluck('id')->all());
        $this->assertTrue($this->period(10, 1)->closed);
        $this->assertSame([$a['id'], $b['id'], $c['id']], array_column($this->getJson("/lines/{$arrastreId}/arrastre-detail")->json(), 'id'));
    }

    public function test_uses_estimated_when_actual_is_null(): void
    {
        $p = $this->october();
        $this->addLine($p['id'], ['name' => 'X', 'estimated' => '5.000']);
        $this->postJson("/periods/{$p['id']}/close")->assertCreated();

        $this->assertSame([500000, 500000], [$this->arrastres(10, 2)[0]->toView()['estimated'], $this->arrastres(10, 2)[0]->toView()['actual']]);
    }

    public function test_nothing_unpaid_closes_without_arrastre(): void
    {
        $p = $this->october();
        $this->addLine($p['id'], ['name' => 'X', 'estimated' => '5.000', 'paid' => true]);

        $this->getJson("/periods/{$p['id']}/close-preview")->assertOk()->assertExactJson(['lines' => [], 'total' => 0]);
        $this->postJson("/periods/{$p['id']}/close")->assertCreated()->assertJson(['arrastreLineId' => null]);
        $this->assertTrue($this->period(10, 1)->closed);
        $this->assertDatabaseMissing('lines', ['source' => 'carried']);
    }

    public function test_closing_twice_yields_one_arrastre_line(): void
    {
        $p = $this->october();
        $this->addLine($p['id'], ['name' => 'X', 'estimated' => '5.000']);
        $this->postJson("/periods/{$p['id']}/close")->assertCreated();
        $this->postJson("/periods/{$p['id']}/close")->assertCreated()->assertJson(['arrastreLineId' => null]);

        $this->assertCount(1, $this->arrastres(10, 2));
    }

    public function test_does_not_duplicate_templates_when_next_period_exists(): void
    {
        $this->seedTemplates();
        $p = $this->october();
        $this->makePeriod(['year' => 2026, 'month' => 10, 'half' => 2]);
        $this->addLine($p['id'], ['name' => 'X', 'estimated' => '5.000']);
        $before = LineModel::where('period_id', $this->period(10, 2)->id)->where('source', 'template')->count();

        $this->postJson("/periods/{$p['id']}/close")->assertCreated();

        $this->assertSame($before, LineModel::where('period_id', $this->period(10, 2)->id)->where('source', 'template')->count());
        $this->assertCount(1, $this->arrastres(10, 2));
    }

    public function test_chains_over_three_periods(): void
    {
        $p = $this->october();
        $a = $this->addLine($p['id'], ['name' => 'A', 'estimated' => '1']);
        $b = $this->addLine($p['id'], ['name' => 'B', 'estimated' => '2']);
        $this->postJson("/periods/{$p['id']}/close")->assertCreated();
        $arr2 = $this->arrastres(10, 2)[0];
        $this->postJson("/periods/{$arr2->period_id}/close")->assertCreated();

        $arr3 = $this->arrastres(11, 1)[0];
        $this->assertSame('Arrastre II Quincena Octubre', $arr3->name);
        $this->assertSame(300, $arr3->toView()['estimated']);
        $this->assertSame([$arr2->id], LineModel::where('carried_to_line_id', $arr3->id)->pluck('id')->all());
        $this->assertSame([$a['id'], $b['id']], LineModel::where('carried_to_line_id', $arr2->id)->orderBy('id')->pluck('id')->all());
    }

    public function test_rejects_when_next_period_is_closed_and_there_is_something_to_carry(): void
    {
        $p = $this->october();
        $next = $this->makePeriod(['year' => 2026, 'month' => 10, 'half' => 2]);
        $this->postJson("/periods/{$next['id']}/close")->assertCreated();
        $this->addLine($p['id'], ['name' => 'X', 'estimated' => '5.000']);

        $this->postJson("/periods/{$p['id']}/close")->assertConflict()->assertJson(['message' => 'La quincena siguiente está cerrada; reabrila primero']);

        $this->assertFalse($this->period(10, 1)->closed);
        $this->assertCount(0, $this->arrastres(10, 2));
    }

    public function test_closes_with_nothing_to_carry_even_if_next_period_is_closed(): void
    {
        $p = $this->october();
        $next = $this->makePeriod(['year' => 2026, 'month' => 10, 'half' => 2]);
        $this->postJson("/periods/{$next['id']}/close")->assertCreated();

        $this->postJson("/periods/{$p['id']}/close")->assertCreated()->assertJson(['arrastreLineId' => null]);
    }

    public function test_unknown_period_is_404(): void
    {
        $this->getJson('/periods/9999/close-preview')->assertNotFound();
        $this->postJson('/periods/9999/close')->assertNotFound();
        $this->postJson('/periods/9999/reopen')->assertNotFound();
    }

    public function test_reopen_deletes_unpaid_arrastre_and_uncarries(): void
    {
        $p = $this->october();
        $a = $this->addLine($p['id'], ['name' => 'A', 'estimated' => '1']);
        $this->postJson("/periods/{$p['id']}/close")->assertCreated();

        $this->postJson("/periods/{$p['id']}/reopen")->assertOk();

        $this->assertCount(0, $this->arrastres(10, 2));
        $this->assertNull(LineModel::find($a['id'])->carried_to_line_id);
        $this->assertFalse($this->period(10, 1)->closed);
    }

    public function test_reopen_rejects_paid_arrastre_changing_nothing(): void
    {
        $p = $this->october();
        $a = $this->addLine($p['id'], ['name' => 'A', 'estimated' => '1']);
        $arrastreId = $this->postJson("/periods/{$p['id']}/close")->json('arrastreLineId');
        $this->postJson("/lines/{$arrastreId}/toggle-paid")->assertOk();

        $this->postJson("/periods/{$p['id']}/reopen")->assertConflict()->assertJson(['message' => 'No se puede reabrir: el arrastre ya está pagado']);

        $this->assertSame($arrastreId, LineModel::find($a['id'])->carried_to_line_id);
        $this->assertTrue($this->period(10, 1)->closed);

        $this->postJson("/lines/{$arrastreId}/toggle-paid")->assertOk();
        $this->postJson("/periods/{$p['id']}/reopen")->assertOk();
    }

    public function test_reopen_rejects_arrastre_carried_onward_and_works_in_order(): void
    {
        $p = $this->october();
        $a = $this->addLine($p['id'], ['name' => 'A', 'estimated' => '1']);
        $this->postJson("/periods/{$p['id']}/close")->assertCreated();
        $arr2 = $this->arrastres(10, 2)[0];
        $this->postJson("/periods/{$arr2->period_id}/close")->assertCreated();

        $this->postJson("/periods/{$p['id']}/reopen")->assertConflict()->assertJson(['message' => 'Reabrí primero la quincena siguiente']);
        $this->assertCount(1, $this->arrastres(11, 1));
        $this->assertTrue($this->period(10, 1)->closed);

        $this->postJson("/periods/{$arr2->period_id}/reopen")->assertOk();
        $this->assertCount(0, $this->arrastres(11, 1));
        $this->assertNull(LineModel::find($arr2->id)->carried_to_line_id);
        $this->postJson("/periods/{$p['id']}/reopen")->assertOk();
        $this->assertFalse($this->period(10, 1)->closed);
        $this->assertSame($a['id'], LineModel::find($a['id'])->id);
    }

    public function test_reopens_a_period_with_nothing_carried(): void
    {
        $p = $this->october();
        $this->postJson("/periods/{$p['id']}/close")->assertCreated();
        $this->postJson("/periods/{$p['id']}/reopen")->assertOk();

        $this->assertFalse($this->period(10, 1)->closed);
    }

    public function test_carried_lines_and_arrastre_are_protected(): void
    {
        $p = $this->october();
        $l = $this->addLine($p['id'], ['name' => 'Deuda', 'estimated' => '5.000']);
        $arrastreId = $this->postJson("/periods/{$p['id']}/close")->json('arrastreLineId');

        $this->putJson("/lines/{$l['id']}/update", ['name' => 'z'])->assertConflict();
        $this->postJson("/lines/{$l['id']}/toggle-paid")->assertConflict();
        $this->deleteJson("/lines/{$l['id']}/delete")->assertConflict();
        $this->deleteJson("/lines/{$arrastreId}/delete")->assertConflict()->assertJson(['message' => 'Para quitar el arrastre, reabrí la quincena anterior']);
        $this->assertCount(1, $this->getJson("/lines/{$arrastreId}/arrastre-detail")->json());
        $this->getJson("/periods/{$p['id']}/data")->assertJsonPath('nextId', $this->period(10, 2)->id);
    }

    public function test_close_and_reopen_read_the_period_lines_with_a_lock(): void
    {
        // FOR UPDATE is a no-op on sqlite, so assert the close path asks for the locking read instead of the blocking.
        $p = $this->october();
        $this->addLine($p['id'], ['name' => 'X', 'estimated' => '5.000']);

        $calls = [];
        $spy = $this->partialMock(LineQueryRepository::class, function ($mock) use (&$calls) {
            $mock->shouldReceive('listByPeriod')->andReturnUsing(function (int $id, bool $lock = false) use (&$calls) {
                $calls[] = $lock;

                return LineModel::query()->where('period_id', $id)->orderBy('sort')->orderBy('id')->get();
            });
        });

        $this->postJson("/periods/{$p['id']}/close")->assertCreated();
        $this->postJson("/periods/{$p['id']}/reopen")->assertOk();

        $this->assertSame([true, true], $calls);
    }
}
