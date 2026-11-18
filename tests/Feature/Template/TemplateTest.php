<?php

namespace Tests\Feature\Template;

use Tests\TestCase;
use App\Models\Period\LineModel;
use App\Models\Template\TemplateModel;

class TemplateTest extends TestCase
{
    /** @var array<string, string> */
    private array $base = ['name' => 'Agua', 'category' => 'servicio', 'estimated' => '1.000'];

    /**
     * The templates page renders.
     */
    public function test_index_page(): void
    {
        $this->get('/templates')->assertOk()->assertInertia(fn ($page) => $page->component('Template/Index', false));
    }

    /**
     * Create, update, list and delete a template.
     */
    public function test_crud_round_trip(): void
    {
        $t = $this->postJson('/templates/store', ['name' => 'Luz', 'category' => 'servicio', 'estimated' => '30.000', 'frequency' => 'mensual_repartido'])
            ->assertCreated()->json('template');
        $this->assertSame(['spaceId' => $this->primarySpaceId(), 'name' => 'Luz', 'estimated' => 3000000, 'dayOfMonth' => null, 'active' => true], array_intersect_key($t, array_flip(['spaceId', 'name', 'estimated', 'dayOfMonth', 'active'])));

        $u = $this->putJson("/templates/{$t['id']}/update", ['estimated' => '40.000', 'active' => false])->assertOk()->json('template');
        $this->assertSame(4000000, $u['estimated']);
        $this->assertFalse($u['active']);
        $this->assertSame('Luz', $u['name']);

        $this->getJson('/templates/get-template-list')->assertOk()->assertJsonCount(1)->assertJsonPath('0.estimated', 4000000);
        $this->deleteJson("/templates/{$t['id']}/delete")->assertOk();
        $this->getJson('/templates/get-template-list')->assertOk()->assertExactJson([]);
        $this->assertTrue(TemplateModel::withoutGlobalScopes()->find($t['id'])->IsDeleted);
    }

    /**
     * A deleted template is not expanded into new periods and its existing lines lose the reference, as before.
     */
    public function test_deleted_template_is_not_expanded_and_lines_are_detached(): void
    {
        $t = $this->postJson('/templates/store', $this->base + ['frequency' => 'cada_periodo'])->assertCreated()->json('template');
        $first = $this->postJson('/periods/store', $this->dates(11, 1))->assertCreated()->json('period');
        $this->assertSame($t['id'], LineModel::query()->where('FkPeriod', $first['id'])->sole()->FkTemplate);

        $this->deleteJson("/templates/{$t['id']}/delete")->assertOk();

        $this->assertNull(LineModel::query()->where('FkPeriod', $first['id'])->sole()->FkTemplate);
        $second = $this->postJson('/periods/store', $this->dates(11, 2))->assertCreated()->json('period');
        $this->assertSame(0, LineModel::query()->where('FkPeriod', $second['id'])->count());
    }

    /**
     * dayOfMonth is required (1 to 31) only for mensual_dia; the old frequencies are rejected.
     */
    public function test_day_of_month_required_only_for_mensual_dia(): void
    {
        $this->postJson('/templates/store', $this->base + ['frequency' => 'mensual_dia'])->assertStatus(422)->assertJsonValidationErrors('dayOfMonth');
        $this->postJson('/templates/store', $this->base + ['frequency' => 'mensual_dia', 'dayOfMonth' => 32])->assertStatus(422)->assertJsonValidationErrors('dayOfMonth');
        $this->postJson('/templates/store', $this->base + ['frequency' => 'mensual_dia', 'dayOfMonth' => 16])->assertCreated()->assertJsonPath('template.dayOfMonth', 16);
        $this->postJson('/templates/store', $this->base + ['frequency' => 'cada_periodo', 'dayOfMonth' => 2])->assertCreated()->assertJsonPath('template.dayOfMonth', null);
        $this->postJson('/templates/store', $this->base + ['frequency' => 'quincenal'])->assertStatus(422)->assertJsonValidationErrors('frequency');
        $this->postJson('/templates/store', $this->base + ['frequency' => 'cada_periodo', 'spaceId' => 999])->assertNotFound();
    }

    /**
     * Invalid category, frequency, name and amount are rejected.
     */
    public function test_rejects_invalid_fields(): void
    {
        $this->postJson('/templates/store', ['category' => 'x'] + $this->base + ['frequency' => 'cada_periodo'])->assertStatus(422)->assertJsonValidationErrors('category');
        $this->postJson('/templates/store', $this->base + ['frequency' => 'nope'])->assertStatus(422)->assertJsonValidationErrors('frequency');
        $this->postJson('/templates/store', ['name' => '  '] + $this->base + ['frequency' => 'cada_periodo'])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/templates/store', ['estimated' => 'abc'] + $this->base + ['frequency' => 'cada_periodo'])->assertStatus(422)->assertJsonValidationErrors(['estimated' => 'Monto inválido']);
        $this->assertSame(0, TemplateModel::count());
    }

    /**
     * Update validates the merged result and clears dayOfMonth when the frequency moves away.
     */
    public function test_update_merges_day_of_month_rules(): void
    {
        $id = $this->postJson('/templates/store', $this->base + ['frequency' => 'cada_periodo'])->assertCreated()->json('template.id');

        $this->putJson("/templates/$id/update", ['frequency' => 'mensual_dia'])->assertStatus(422)->assertJsonValidationErrors('dayOfMonth');
        $this->putJson("/templates/$id/update", ['frequency' => 'mensual_dia', 'dayOfMonth' => 1])->assertOk()->assertJsonPath('template.dayOfMonth', 1);
        $this->putJson("/templates/$id/update", ['dayOfMonth' => 0])->assertStatus(422);
        $this->putJson("/templates/$id/update", ['dayOfMonth' => 16])->assertOk()->assertJsonPath('template.dayOfMonth', 16);
        $this->putJson("/templates/$id/update", ['frequency' => 'mensual_repartido'])->assertOk()->assertJsonPath('template.dayOfMonth', null);
        $this->assertNull(TemplateModel::find($id)->DayOfMonth);
    }

    /**
     * Update and delete of a missing template answer 404.
     */
    public function test_missing_template_is_404(): void
    {
        $this->putJson('/templates/999/update', ['active' => false])->assertNotFound();
        $this->deleteJson('/templates/999/delete')->assertNotFound();
    }
}
