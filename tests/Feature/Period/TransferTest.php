<?php

namespace Tests\Feature\Period;

use App\Models\Period\LineModel;
use App\Models\Period\PeriodModel;
use App\Models\Template\TemplateModel;

class TransferTest extends PeriodTestCase
{
    /**
     * Create a space through the HTTP endpoint.
     *
     * @param  string  $name  The name.
     * @param  string  $frequency  The frequency.
     * @return int The space identifier.
     */
    private function space(string $name = 'Ahorro', string $frequency = 'monthly'): int
    {
        return $this->postJson('/spaces/store', ['name' => $name, 'frequency' => $frequency])->assertCreated()->json('space.id');
    }

    /**
     * Create a period of a space.
     *
     * @param  int  $spaceId  The space identifier.
     * @param  string  $start  The start date.
     * @param  string  $end  The end date.
     * @return int The period identifier.
     */
    private function spacePeriod(int $spaceId, string $start, string $end): int
    {
        return $this->postJson('/periods/store', ['spaceId' => $spaceId, 'startDate' => $start, 'endDate' => $end])->assertCreated()->json('period.id');
    }

    /**
     * Add a transfer line (gasto by default) to a period.
     *
     * @param  int  $periodId  The period identifier.
     * @param  int  $targetSpaceId  The target space.
     * @param  array<string, mixed>  $extra  Extra fields.
     * @return array<string, mixed> The line view.
     */
    private function transfer(int $periodId, int $targetSpaceId, array $extra = []): array
    {
        return $this->postJson("/periods/{$periodId}/lines/store", $extra + ['category' => 'ahorro', 'name' => 'Ahorro mes', 'estimated' => '100.000', 'targetSpaceId' => $targetSpaceId])->assertCreated()->json('line');
    }

    /**
     * The live destination line of an origin line.
     *
     * @param  int  $originId  The origin line identifier.
     * @return LineModel|null The destination.
     */
    private function destination(int $originId): ?LineModel
    {
        return LineModel::where('FkTransferFromLine', $originId)->first();
    }

    /**
     * The periods of a space as start dates.
     *
     * @param  int  $spaceId  The space identifier.
     * @return list<string> The start dates.
     */
    private function starts(int $spaceId): array
    {
        return PeriodModel::where('FkSpace', $spaceId)->orderBy('StartDate')->pluck('StartDate')->all();
    }

    /**
     * The restante of a period.
     *
     * @param  int  $periodId  The period identifier.
     * @return int The restante in cents.
     */
    private function restante(int $periodId): int
    {
        return $this->getJson("/periods/{$periodId}/data")->assertOk()->json('summary.restante');
    }

    public function test_pay_creates_destination_and_moves_exactly_the_amount(): void
    {
        $ahorro = $this->space();
        $origin = $this->makePeriod()['id'];
        $this->postJson("/periods/{$origin}/lines/store", ['category' => 'ingreso', 'name' => 'Sueldo', 'estimated' => '500.000']);
        $line = $this->transfer($origin, $ahorro);
        $this->assertSame([$ahorro, null, null], [$line['targetSpaceId'], $line['transferFromLineId'], $line['transferFromSpaceName']]);
        $this->assertNull($this->destination($line['id']));
        $this->assertSame([], $this->starts($ahorro));

        $this->postJson("/lines/{$line['id']}/toggle-paid")->assertOk();

        // Ahorro had no periods: the first one aligned to its frequency that contains 1 Nov is created.
        $this->assertSame(['2026-11-01'], $this->starts($ahorro));
        $dest = $this->destination($line['id'])->toView();
        $this->assertSame(['ingreso', 'Desde Salario: Ahorro mes', 10000000, 10000000, true, 'transfer', $line['id'], 'Salario', null], [
            $dest['category'], $dest['name'], $dest['estimated'], $dest['actual'], $dest['paid'], $dest['source'], $dest['transferFromLineId'], $dest['transferFromSpaceName'], $dest['targetSpaceId'],
        ]);
        $this->assertSame('Salario', collect($this->getJson("/periods/{$dest['periodId']}/data")->json('lines'))->firstWhere('id', $dest['id'])['transferFromSpaceName']);
        $this->assertSame(40000000, $this->restante($origin));
        $this->assertSame(10000000, $this->restante($dest['periodId']));
    }

    public function test_store_paid_transfer_creates_destination(): void
    {
        $ahorro = $this->space();
        $line = $this->transfer($this->makePeriod()['id'], $ahorro, ['paid' => true, 'actual' => '90.000']);

        $this->assertSame(9000000, $this->destination($line['id'])->toView()['estimated']);
    }

    public function test_biweekly_origin_sends_two_transfers_to_the_same_monthly_period(): void
    {
        $ahorro = $this->space();
        $a = $this->transfer($this->makePeriod($this->dates(11, 1))['id'], $ahorro, ['paid' => true]);
        $b = $this->transfer($this->makePeriod($this->dates(11, 2))['id'], $ahorro, ['paid' => true]);

        $this->assertSame(['2026-11-01'], $this->starts($ahorro));
        $periodId = PeriodModel::where('FkSpace', $ahorro)->value('IdPeriod');
        $this->assertSame([$periodId, $periodId], [$this->destination($a['id'])->FkPeriod, $this->destination($b['id'])->FkPeriod]);
        $this->assertSame(20000000, $this->restante($periodId));
    }

    public function test_unpay_deletes_destination(): void
    {
        $line = $this->transfer($this->makePeriod()['id'], $this->space(), ['paid' => true]);
        $this->postJson("/lines/{$line['id']}/toggle-paid")->assertOk();
        $this->assertNull($this->destination($line['id']));

        $this->putJson("/lines/{$line['id']}/update", ['paid' => true])->assertOk();
        $this->assertNotNull($this->destination($line['id']));
        $this->putJson("/lines/{$line['id']}/update", ['paid' => false])->assertOk();
        $this->assertNull($this->destination($line['id']));
    }

    public function test_amount_and_name_edits_update_destination(): void
    {
        $line = $this->transfer($this->makePeriod()['id'], $this->space(), ['paid' => true]);
        $destId = $this->destination($line['id'])->IdLine;

        $this->putJson("/lines/{$line['id']}/update", ['actual' => '75.500', 'name' => 'Fondo'])->assertOk();

        $dest = $this->destination($line['id']);
        $this->assertSame([$destId, 'Desde Salario: Fondo', 7550000], [$dest->IdLine, $dest->Name, $dest->toView()['estimated']]);
    }

    public function test_target_change_moves_destination(): void
    {
        $ahorro = $this->space();
        $viaje = $this->space('Viaje', 'weekly');
        $line = $this->transfer($this->makePeriod()['id'], $ahorro, ['paid' => true]);
        $old = $this->destination($line['id']);

        $this->putJson("/lines/{$line['id']}/update", ['targetSpaceId' => $viaje])->assertOk()->assertJsonPath('line.targetSpaceId', $viaje);

        $this->assertTrue(LineModel::withoutGlobalScopes()->find($old->IdLine)->IsDeleted);
        $new = $this->destination($line['id']);
        $this->assertSame($viaje, $new->period->FkSpace);
        $this->assertSame(['2026-11-01', '2026-11-07'], [$new->period->StartDate, $new->period->EndDate]);

        $this->putJson("/lines/{$line['id']}/update", ['targetSpaceId' => null])->assertOk();
        $this->assertNull($this->destination($line['id']));
    }

    public function test_delete_origin_deletes_destination(): void
    {
        $line = $this->transfer($this->makePeriod()['id'], $this->space(), ['paid' => true]);
        $this->deleteJson("/lines/{$line['id']}/delete")->assertOk();

        $this->assertNull($this->destination($line['id']));
    }

    public function test_destination_lines_are_read_only(): void
    {
        $line = $this->transfer($this->makePeriod()['id'], $this->space(), ['paid' => true]);
        $destId = $this->destination($line['id'])->IdLine;

        $this->putJson("/lines/{$destId}/update", ['name' => 'x'])->assertStatus(409)->assertJsonPath('message', 'Se edita desde el espacio de origen');
        $this->postJson("/lines/{$destId}/toggle-paid")->assertStatus(409)->assertJsonPath('message', 'Se edita desde el espacio de origen');
        $this->deleteJson("/lines/{$destId}/delete")->assertStatus(409)->assertJsonPath('message', 'Se edita desde el espacio de origen');
    }

    public function test_invalid_targets_are_422(): void
    {
        $ahorro = $this->space();
        $p = $this->makePeriod()['id'];
        $base = ['category' => 'gasto', 'name' => 'x', 'estimated' => '1.000'];

        $this->postJson("/periods/{$p}/lines/store", $base + ['targetSpaceId' => $this->primarySpaceId()])->assertStatus(422)->assertJsonPath('errors.targetSpaceId.0', 'No se puede enviar al mismo espacio');
        $this->postJson("/periods/{$p}/lines/store", ['category' => 'ingreso'] + $base + ['targetSpaceId' => $ahorro])->assertStatus(422)->assertJsonPath('errors.targetSpaceId.0', 'Solo un egreso se puede enviar a otro espacio');
        $this->postJson("/periods/{$p}/lines/store", $base + ['targetSpaceId' => 999])->assertStatus(422)->assertJsonValidationErrors(['targetSpaceId' => 'Espacio inválido']);
        $this->postJson("/periods/{$p}/lines/store", $base + ['targetSpaceId' => 'abc'])->assertStatus(422)->assertJsonValidationErrors(['targetSpaceId' => 'Espacio inválido']);
        $this->assertSame(0, LineModel::count());

        // A category change on an existing transfer is checked against the merged state and rolled back.
        $line = $this->transfer($p, $ahorro, ['paid' => true]);
        $this->putJson("/lines/{$line['id']}/update", ['category' => 'ingreso'])->assertStatus(422);
        $this->assertSame('ahorro', LineModel::find($line['id'])->Category);
        $this->assertNotNull($this->destination($line['id']));
    }

    public function test_missing_destination_periods_are_created_forward(): void
    {
        $ahorro = $this->space();
        $this->spacePeriod($ahorro, '2026-08-01', '2026-08-31');
        $this->transfer($this->makePeriod()['id'], $ahorro, ['paid' => true]);

        $this->assertSame(['2026-08-01', '2026-09-01', '2026-10-01', '2026-11-01'], $this->starts($ahorro));
    }

    public function test_more_than_twelve_missing_periods_is_409_and_rolls_back(): void
    {
        $ahorro = $this->space();
        $this->spacePeriod($ahorro, '2025-10-01', '2025-10-31'); // Nov 2025 .. Nov 2026 would be 13 periods
        $line = $this->transfer($this->makePeriod()['id'], $ahorro);

        $this->postJson("/lines/{$line['id']}/toggle-paid")->assertStatus(409)->assertJsonPath('message', 'Creá primero el periodo en Ahorro');

        $this->assertSame(['2025-10-01'], $this->starts($ahorro));
        $this->assertFalse(LineModel::find($line['id'])->Paid);
    }

    public function test_twelve_missing_periods_are_created(): void
    {
        $ahorro = $this->space();
        $this->spacePeriod($ahorro, '2025-11-01', '2025-11-30');
        $this->transfer($this->makePeriod()['id'], $ahorro, ['paid' => true]);

        $this->assertCount(13, $this->starts($ahorro));
    }

    public function test_date_before_the_first_destination_period_is_409(): void
    {
        $ahorro = $this->space();
        $this->spacePeriod($ahorro, '2026-12-01', '2026-12-31');
        $line = $this->transfer($this->makePeriod()['id'], $ahorro);

        $this->postJson("/lines/{$line['id']}/toggle-paid")->assertStatus(409)->assertJsonPath('message', 'Creá primero el periodo en Ahorro');
    }

    public function test_only_manual_or_template_lines_can_get_a_target(): void
    {
        $ahorro = $this->space();
        $p = $this->makePeriod($this->dates(11, 2))['id'];
        $this->postJson("/periods/{$p}/lines/store", ['category' => 'gasto', 'name' => 'Super', 'estimated' => '5.000', 'paid' => true]);
        $this->postJson("/periods/{$p}/lines/store", ['category' => 'gasto', 'name' => 'Otro', 'estimated' => '1.000']);
        $this->postJson("/periods/{$p}/close")->assertSuccessful();
        $arrastre = LineModel::where('Source', 'carried')->firstOrFail();
        $installments = LineModel::where('Source', 'manual')->where('Name', 'Super')->firstOrFail();
        $installments->update(['Source' => 'installments']);

        foreach ([$arrastre, $installments] as $line) {
            $this->putJson("/lines/{$line->IdLine}/update", ['targetSpaceId' => $ahorro])->assertStatus(422)->assertJsonPath('errors.targetSpaceId.0', 'Esta línea no se puede enviar a otro espacio');
        }
        $this->assertNull(LineModel::find($arrastre->IdLine)->FkTargetSpace);
    }

    public function test_reopening_origin_keeps_paid_transfer_destination(): void
    {
        $p = $this->makePeriod()['id'];
        $line = $this->transfer($p, $this->space(), ['paid' => true]);
        $this->postJson("/periods/{$p}/close")->assertSuccessful();
        $this->postJson("/periods/{$p}/reopen")->assertSuccessful();

        $this->assertNotNull($this->destination($line['id']));
    }

    public function test_close_carries_unpaid_transfer_as_its_own_line_and_paying_it_uses_its_period(): void
    {
        $ahorro = $this->space();
        $p = $this->makePeriod($this->dates(11, 2))['id'];
        $line = $this->transfer($p, $ahorro);
        $this->postJson("/periods/{$p}/lines/store", ['category' => 'gasto', 'name' => 'Super', 'estimated' => '5.000']);

        $this->postJson("/periods/{$p}/close")->assertSuccessful();

        $next = $this->period(12, 1)->IdPeriod;
        $arrastre = LineModel::where(['FkPeriod' => $next, 'Name' => 'Arrastre II Quincena Noviembre 2026'])->firstOrFail();
        $this->assertSame(500000, $arrastre->toView()['estimated']);
        $carried = LineModel::where(['FkPeriod' => $next, 'Name' => '↩ Ahorro mes'])->firstOrFail()->toView();
        $this->assertSame(['ahorro', $ahorro, 10000000, false], [$carried['category'], $carried['targetSpaceId'], $carried['estimated'], $carried['paid']]);
        $this->assertSame($carried['id'], LineModel::find($line['id'])->FkCarriedToLine);

        $this->postJson("/lines/{$carried['id']}/toggle-paid")->assertOk();

        // The carried line's period starts 1 Dec, so the money lands in Ahorro's December period.
        $this->assertSame('2026-12-01', $this->destination($carried['id'])->period->StartDate);
    }

    public function test_reopen_undoes_unpaid_carried_transfer(): void
    {
        $p = $this->makePeriod()['id'];
        $line = $this->transfer($p, $this->space());
        $this->postJson("/periods/{$p}/close")->assertSuccessful();
        $carriedId = LineModel::find($line['id'])->FkCarriedToLine;

        $this->postJson("/periods/{$p}/reopen")->assertSuccessful();

        $this->assertNull(LineModel::find($carriedId));
        $this->assertNull(LineModel::find($line['id'])->FkCarriedToLine);
    }

    public function test_reopen_is_blocked_when_carried_transfer_was_paid(): void
    {
        $p = $this->makePeriod()['id'];
        $line = $this->transfer($p, $this->space());
        $this->postJson("/periods/{$p}/close")->assertSuccessful();
        $carriedId = LineModel::find($line['id'])->FkCarriedToLine;
        $this->postJson("/lines/{$carriedId}/toggle-paid")->assertOk();

        $this->postJson("/periods/{$p}/reopen")->assertStatus(409)->assertJsonPath('message', 'No se puede reabrir: el arrastre ya está pagado');
        $this->assertTrue($this->period(11, 1)->Closed);
    }

    public function test_reopen_is_blocked_when_carried_transfer_was_carried_onward(): void
    {
        $p = $this->makePeriod()['id'];
        $line = $this->transfer($p, $this->space());
        $this->postJson("/periods/{$p}/close")->assertSuccessful();
        $this->postJson('/periods/'.$this->period(11, 2)->IdPeriod.'/close')->assertSuccessful();

        $this->assertSame('↩ Ahorro mes', LineModel::where('FkPeriod', $this->period(12, 1)->IdPeriod)->value('Name'));
        $this->postJson("/periods/{$p}/reopen")->assertStatus(409)->assertJsonPath('message', 'Reabrí primero el periodo siguiente');
    }

    public function test_templates_with_target_create_unpaid_transfer_lines(): void
    {
        $ahorro = $this->space();
        $this->postJson('/templates/store', ['name' => 'Ahorro', 'category' => 'ahorro', 'estimated' => '50.000', 'frequency' => 'cada_periodo', 'targetSpaceId' => $ahorro])
            ->assertCreated()->assertJsonPath('template.targetSpaceId', $ahorro);
        $this->postJson('/templates/store', ['name' => 'X', 'category' => 'ahorro', 'estimated' => '1', 'frequency' => 'cada_periodo', 'targetSpaceId' => $this->primarySpaceId()])->assertStatus(422);
        $id = TemplateModel::value('IdTemplate');
        $this->putJson("/templates/{$id}/update", ['category' => 'ingreso'])->assertStatus(422);

        $p = $this->makePeriod()['id'];
        $line = LineModel::where('FkPeriod', $p)->firstOrFail();
        $this->assertSame([$ahorro, false], [$line->toView()['targetSpaceId'], $line->Paid]);
        $this->assertNull($this->destination($line->IdLine));

        $this->postJson("/lines/{$line->IdLine}/toggle-paid")->assertOk();
        $this->assertNotNull($this->destination($line->IdLine));
    }
}
