<?php

namespace Tests\Feature\Space;

use Tests\TestCase;
use App\Models\Period\LineModel;
use App\Models\Space\SpaceModel;
use Illuminate\Testing\TestResponse;
use App\Models\Template\TemplateModel;

class SpaceTest extends TestCase
{
    /**
     * Store a space.
     *
     * @param  array<string, mixed>  $extra  Fields that override the defaults.
     * @return TestResponse The response.
     */
    private function store(array $extra = [])
    {
        return $this->postJson('/spaces/store', $extra + ['name' => 'Ahorros', 'frequency' => 'monthly', 'initialBalance' => '5.000']);
    }

    /**
     * The spaces page renders.
     */
    public function test_index_page(): void
    {
        $this->get('/spaces')->assertOk()->assertInertia(fn ($page) => $page->component('Space/Index', false));
    }

    /**
     * Create, update, list and delete a space; the new one is not primary by default.
     */
    public function test_crud_round_trip(): void
    {
        $s = $this->store()->assertCreated()->json('space');
        $this->assertSame(['name' => 'Ahorros', 'frequency' => 'monthly', 'customDays' => null, 'initialBalance' => 500000, 'isPrimary' => false, 'hasPeriods' => false], array_diff_key($s, ['id' => 1]));

        $u = $this->putJson("/spaces/{$s['id']}/update", ['frequency' => 'custom', 'customDays' => 10, 'initialBalance' => '0'])->assertOk()->json('space');
        $this->assertSame(['custom', 10, 0], [$u['frequency'], $u['customDays'], $u['initialBalance']]);
        $this->assertNull($this->putJson("/spaces/{$s['id']}/update", ['frequency' => 'weekly'])->assertOk()->json('space.customDays'));

        $this->getJson('/spaces/get-space-list')->assertOk()->assertJsonCount(2)->assertJsonPath('1.name', 'Ahorros');
        $this->deleteJson("/spaces/{$s['id']}/delete")->assertOk();
        $this->getJson('/spaces/get-space-list')->assertJsonCount(1);
        $this->assertTrue(SpaceModel::withoutGlobalScopes()->find($s['id'])->IsDeleted);
        $this->putJson("/spaces/{$s['id']}/update", ['name' => 'x'])->assertNotFound();
    }

    /**
     * Frequency, custom days, name and amount are validated; a deleted space frees its name.
     */
    public function test_validation(): void
    {
        $this->store(['frequency' => 'daily'])->assertUnprocessable()->assertJsonValidationErrors(['frequency']);
        $this->store(['frequency' => 'custom'])->assertUnprocessable()->assertJsonValidationErrors(['customDays']);
        $this->store(['frequency' => 'custom', 'customDays' => 367])->assertUnprocessable()->assertJsonValidationErrors(['customDays']);
        $this->store(['frequency' => 'custom', 'customDays' => 0])->assertUnprocessable()->assertJsonValidationErrors(['customDays']);
        $this->store(['initialBalance' => 'abc'])->assertUnprocessable()->assertJsonPath('errors.initialBalance.0', 'Monto inválido');
        $this->store(['name' => ' '])->assertUnprocessable()->assertJsonValidationErrors(['name']);

        $custom = $this->store(['name' => 'Custom', 'frequency' => 'custom', 'customDays' => 366, 'initialBalance' => null])->assertCreated()->json('space');
        $this->assertSame([366, 0], [$custom['customDays'], $custom['initialBalance']]);

        $this->store(['name' => 'Salario'])->assertUnprocessable()->assertJsonValidationErrors(['name']);
        $this->store(['name' => 'Ahorros'])->assertCreated();
        $second = $this->store(['name' => 'Ahorros'])->assertUnprocessable()->assertJsonValidationErrors(['name']);
        $second->assertJsonPath('errors.name.0', 'Ya existe un espacio con ese nombre');
        $this->putJson("/spaces/{$custom['id']}/update", ['name' => 'Ahorros'])->assertUnprocessable()->assertJsonValidationErrors(['name']);
        $this->putJson("/spaces/{$custom['id']}/update", ['name' => 'Ahorros', 'frequency' => 'custom'])->assertUnprocessable();
        $this->putJson("/spaces/{$custom['id']}/update", ['frequency' => 'monthly', 'name' => $custom['name']])->assertOk();

        $other = SpaceModel::where('Name', 'Ahorros')->firstOrFail();
        $this->deleteJson("/spaces/{$other->IdSpace}/delete")->assertOk();
        $this->store(['name' => 'Ahorros'])->assertCreated();
    }

    /**
     * Exactly one primary: storing with isPrimary or make-primary switches it in one step.
     */
    public function test_primary_switch(): void
    {
        $salario = $this->primarySpaceId();
        $a = $this->store(['isPrimary' => true])->assertCreated()->json('space');
        $this->assertTrue($a['isPrimary']);
        $this->assertSame([$a['id']], SpaceModel::where('IsPrimary', true)->pluck('IdSpace')->all());

        $this->postJson("/spaces/{$salario}/make-primary")->assertOk()->assertJsonPath('space.isPrimary', true);
        $this->assertSame([$salario], SpaceModel::where('IsPrimary', true)->pluck('IdSpace')->all());
        $this->postJson("/spaces/{$salario}/make-primary")->assertOk();
        $this->assertSame(1, SpaceModel::where('IsPrimary', true)->count());
        $this->postJson('/spaces/9999/make-primary')->assertNotFound();

        $this->putJson("/spaces/{$a['id']}/update", ['isPrimary' => true])->assertOk();
        $this->assertSame([$salario], SpaceModel::where('IsPrimary', true)->pluck('IdSpace')->all());
    }

    /**
     * The primary space cannot be deleted; a space with periods, cards, templates or targeting lines/templates cannot either.
     */
    public function test_delete_rules(): void
    {
        $this->deleteJson('/spaces/'.$this->primarySpaceId().'/delete')->assertStatus(409)->assertJsonPath('message', 'No se puede borrar el espacio principal');
        $this->deleteJson('/spaces/9999/delete')->assertNotFound();

        $withPeriod = $this->store(['name' => 'P'])->assertCreated()->json('space.id');
        $this->postJson('/periods/store', ['spaceId' => $withPeriod, 'startDate' => '2026-11-01', 'endDate' => '2026-11-30'])->assertCreated();
        $withCard = $this->store(['name' => 'C'])->assertCreated()->json('space.id');
        $this->postJson('/cards/store', ['name' => 'Visa', 'spaceId' => $withCard])->assertCreated();
        $withTemplate = $this->store(['name' => 'T'])->assertCreated()->json('space.id');
        $this->postJson('/templates/store', ['name' => 'Luz', 'category' => 'servicio', 'estimated' => '1', 'frequency' => 'cada_periodo', 'spaceId' => $withTemplate])->assertCreated();
        $targeted = $this->store(['name' => 'D'])->assertCreated()->json('space.id');
        $line = $this->postJson('/periods/'.$this->makePeriodId().'/lines/store', ['category' => 'gasto', 'name' => 'x', 'estimated' => '1'])->assertCreated()->json('line.id');
        LineModel::where('IdLine', $line)->update(['FkTargetSpace' => $targeted]);
        $targetedByTemplate = $this->store(['name' => 'E'])->assertCreated()->json('space.id');
        TemplateModel::query()->update(['FkTargetSpace' => $targetedByTemplate]);

        foreach ([$withPeriod, $withCard, $withTemplate, $targeted, $targetedByTemplate] as $id) {
            $this->deleteJson("/spaces/{$id}/delete")->assertStatus(409)->assertJsonPath('message', 'El espacio tiene datos; no se puede borrar');
        }
        $empty = $this->store(['name' => 'Vacío'])->assertCreated()->json('space.id');
        $this->deleteJson("/spaces/{$empty}/delete")->assertOk();
    }

    /**
     * A space with periods keeps its frequency and days (the cuota and template splits depend on them); other fields still change.
     */
    public function test_frequency_is_locked_once_the_space_has_periods(): void
    {
        $id = $this->store(['name' => 'P', 'frequency' => 'custom', 'customDays' => 10])->assertCreated()->json('space.id');
        $this->postJson('/periods/store', ['spaceId' => $id, 'startDate' => '2026-11-01', 'endDate' => '2026-11-10'])->assertCreated();
        $message = 'No se puede cambiar la frecuencia de un espacio que ya tiene periodos';

        $this->putJson("/spaces/{$id}/update", ['frequency' => 'monthly'])->assertStatus(409)->assertJsonPath('message', $message);
        $this->putJson("/spaces/{$id}/update", ['frequency' => 'custom', 'customDays' => 7])->assertStatus(409)->assertJsonPath('message', $message);
        $this->putJson("/spaces/{$id}/update", ['frequency' => 'custom', 'customDays' => 10, 'name' => 'Q'])->assertOk()->assertJsonPath('space.hasPeriods', true);
        $this->assertSame(['custom', 10], [SpaceModel::find($id)->Frequency, SpaceModel::find($id)->CustomDays]);
        $this->assertTrue(collect($this->getJson('/spaces/get-space-list')->json())->firstWhere('id', $id)['hasPeriods']);
    }

    /**
     * Create a period of the primary space and return its id.
     *
     * @return int The period identifier.
     */
    private function makePeriodId(): int
    {
        return $this->postJson('/periods/store', $this->dates(11, 1))->assertCreated()->json('period.id');
    }

    /**
     * Cards and templates are listed and stored per space (primary by default); an unknown space is a 404.
     */
    public function test_lists_are_filtered_by_space(): void
    {
        $other = $this->store()->assertCreated()->json('space.id');
        $this->postJson('/cards/store', ['name' => 'Visa'])->assertCreated();
        $this->postJson('/cards/store', ['name' => 'Master', 'spaceId' => $other])->assertCreated();
        $base = ['category' => 'servicio', 'estimated' => '1', 'frequency' => 'cada_periodo'];
        $this->postJson('/templates/store', $base + ['name' => 'Luz'])->assertCreated();
        $this->postJson('/templates/store', $base + ['name' => 'Agua', 'spaceId' => $other])->assertCreated();

        $this->getJson('/installments/get-card-list')->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Visa');
        $this->getJson("/installments/get-card-list?space={$other}")->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Master');
        $this->getJson('/templates/get-template-list')->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Luz');
        $this->getJson("/templates/get-template-list?space={$other}")->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'Agua');

        $this->getJson('/installments/get-card-list?space=9999')->assertNotFound();
        $this->getJson('/templates/get-template-list?space=abc')->assertNotFound();
    }

    /**
     * Card totals split the monthly cuota across the periods of the space's frequency (porQuincena kept, porPeriodo added).
     */
    public function test_card_totals_follow_the_space_frequency(): void
    {
        $monthly = $this->store(['name' => 'Mensual'])->assertCreated()->json('space.id');
        $card = $this->postJson('/cards/store', ['name' => 'Visa', 'spaceId' => $monthly])->assertCreated()->json('card.id');
        $this->postJson('/installments/store', [
            'cardId' => $card, 'merchant' => 'M', 'item' => 'I', 'type' => 'tasa0', 'totalInstallments' => 3, 'paidInstallments' => 0, 'initialBalance' => '300',
        ])->assertCreated();

        $totals = $this->getJson("/installments/get-card-list?space={$monthly}")->assertOk()->json('0.totals');
        $this->assertSame([10000], $totals['porPeriodo']);
        $this->assertSame([10000], $totals['porQuincena']);
    }
}
