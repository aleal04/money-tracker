<?php

namespace Tests\Feature\Template;

use Tests\TestCase;
use App\Models\Template\TemplateModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

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
        $t = $this->postJson('/templates/store', ['name' => 'Luz', 'category' => 'servicio', 'estimated' => '30.000', 'frequency' => 'mensual_dividido'])
            ->assertCreated()->json('template');
        $this->assertSame(['name' => 'Luz', 'estimated' => 3000000, 'half' => null, 'active' => true], array_intersect_key($t, array_flip(['name', 'estimated', 'half', 'active'])));

        $u = $this->putJson("/templates/{$t['id']}/update", ['estimated' => '40.000', 'active' => false])->assertOk()->json('template');
        $this->assertSame(4000000, $u['estimated']);
        $this->assertFalse($u['active']);
        $this->assertSame('Luz', $u['name']);

        $this->getJson('/templates/get-template-list')->assertOk()->assertJsonCount(1)->assertJsonPath('0.estimated', 4000000);
        $this->deleteJson("/templates/{$t['id']}/delete")->assertOk();
        $this->getJson('/templates/get-template-list')->assertOk()->assertExactJson([]);
    }

    /**
     * Half is required (1 or 2) only for mensual_en_quincena.
     */
    public function test_half_required_only_for_mensual_en_quincena(): void
    {
        $this->postJson('/templates/store', $this->base + ['frequency' => 'mensual_en_quincena'])->assertStatus(422)->assertJsonValidationErrors('half');
        $this->postJson('/templates/store', $this->base + ['frequency' => 'mensual_en_quincena', 'half' => 3])->assertStatus(422)->assertJsonValidationErrors('half');
        $this->postJson('/templates/store', $this->base + ['frequency' => 'mensual_en_quincena', 'half' => 2])->assertCreated()->assertJsonPath('template.half', 2);
        $this->postJson('/templates/store', $this->base + ['frequency' => 'quincenal', 'half' => 2])->assertCreated()->assertJsonPath('template.half', null);
    }

    /**
     * Invalid category, frequency, name and amount are rejected.
     */
    public function test_rejects_invalid_fields(): void
    {
        $this->postJson('/templates/store', ['category' => 'x'] + $this->base + ['frequency' => 'quincenal'])->assertStatus(422)->assertJsonValidationErrors('category');
        $this->postJson('/templates/store', $this->base + ['frequency' => 'nope'])->assertStatus(422)->assertJsonValidationErrors('frequency');
        $this->postJson('/templates/store', ['name' => '  '] + $this->base + ['frequency' => 'quincenal'])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/templates/store', ['estimated' => 'abc'] + $this->base + ['frequency' => 'quincenal'])->assertStatus(422)->assertJsonValidationErrors(['estimated' => 'Monto inválido']);
        $this->assertSame(0, TemplateModel::count());
    }

    /**
     * Update validates the merged result and clears half when the frequency moves away.
     */
    public function test_update_merges_half_rules(): void
    {
        $id = $this->postJson('/templates/store', $this->base + ['frequency' => 'quincenal'])->assertCreated()->json('template.id');

        $this->putJson("/templates/$id/update", ['frequency' => 'mensual_en_quincena'])->assertStatus(422)->assertJsonValidationErrors('half');
        $this->putJson("/templates/$id/update", ['frequency' => 'mensual_en_quincena', 'half' => 1])->assertOk()->assertJsonPath('template.half', 1);
        $this->putJson("/templates/$id/update", ['half' => 5])->assertStatus(422);
        $this->putJson("/templates/$id/update", ['half' => 2])->assertOk()->assertJsonPath('template.half', 2);
        $this->putJson("/templates/$id/update", ['frequency' => 'mensual_dividido'])->assertOk()->assertJsonPath('template.half', null);
        $this->assertNull(TemplateModel::find($id)->half);
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
