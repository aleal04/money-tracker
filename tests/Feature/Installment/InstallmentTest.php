<?php

namespace Tests\Feature\Installment;

use Carbon\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Period\PeriodTestCase;
use App\Models\Installment\InstallmentModel;

class InstallmentTest extends PeriodTestCase
{
    /**
     * Create a card through the endpoint.
     *
     * @param  string  $name  The card name.
     * @return int The card identifier.
     */
    private function mkCard(string $name = 'Visa'): int
    {
        return $this->postJson('/cards/store', ['name' => $name])->assertCreated()->json('card.id');
    }

    /**
     * Post an installment with the Reloj defaults overridden by the given fields.
     *
     * @param  int  $cardId  The card identifier.
     * @param  array<string, mixed>  $override  The fields to override.
     */
    private function mkInst(int $cardId, array $override = []): TestResponse
    {
        return $this->postJson('/installments/store', $override + [
            'cardId' => $cardId, 'merchant' => 'M', 'item' => 'I', 'totalInstallments' => 24, 'paidInstallments' => 12,
            'type' => 'tasa0', 'initialBalance' => '265.022,62',
        ]);
    }

    /**
     * The first card of the list endpoint.
     *
     * @return array<string, mixed> The card view.
     */
    private function firstCard(): array
    {
        return $this->getJson('/installments/get-card-list')->assertOk()->json('0');
    }

    /**
     * The installments page renders.
     */
    public function test_index_page(): void
    {
        $this->get('/installments')->assertOk()->assertInertia(fn ($page) => $page->component('Installment/Index', false));
    }

    /**
     * Cuota, saldo and status are computed; totals exclude terminadas.
     */
    public function test_computes_cuota_saldo_status_and_totals(): void
    {
        $c = $this->mkCard();
        $this->mkInst($c, ['item' => 'Reloj'])->assertCreated();
        $this->mkInst($c, ['item' => 'Camilo', 'type' => 'minicuotas', 'totalInstallments' => 12, 'paidInstallments' => 6, 'initialBalance' => '1', 'installmentAmount' => '98.777,70'])->assertCreated();
        $this->mkInst($c, ['item' => 'Wallet', 'totalInstallments' => 18, 'paidInstallments' => 17, 'initialBalance' => '18.000'])->assertCreated();
        $this->mkInst($c, ['item' => 'Dashcam', 'totalInstallments' => 12, 'paidInstallments' => 12, 'initialBalance' => '12.000'])->assertCreated();

        $card = $this->firstCard();
        $by = fn (string $item) => collect($card['installments'])->firstWhere('item', $item);
        $this->assertSame([1104261, 13251131, 'activa'], [$by('Reloj')['cuota'], $by('Reloj')['saldo']['value'], $by('Reloj')['status']]);
        $this->assertSame([59266620, 'activa'], [$by('Camilo')['saldo']['value'], $by('Camilo')['status']]);
        $this->assertSame('por_terminar', $by('Wallet')['status']);
        $this->assertSame('terminada', $by('Dashcam')['status']);

        $monthly = 1104261 + 9877770 + 100000;
        $this->assertSame($monthly, $card['totals']['cuotaMensual']);
        $this->assertSame(13251131 + 59266620 + 100000, $card['totals']['saldoFaltante']);
        $this->assertSame($monthly, array_sum($card['totals']['porQuincena']));
    }

    /**
     * Invalid input fails with a 422 per field; an unknown card is a 404.
     */
    public function test_validates_input(): void
    {
        $c = $this->mkCard();
        $this->mkInst($c, ['type' => 'minicuotas'])->assertUnprocessable()->assertJsonValidationErrors(['installmentAmount']);
        $this->mkInst($c, ['paidInstallments' => 25])->assertUnprocessable()->assertJsonValidationErrors(['paidInstallments']);
        $this->mkInst($c, ['paidInstallments' => -1])->assertUnprocessable()->assertJsonValidationErrors(['paidInstallments']);
        $this->mkInst($c, ['totalInstallments' => 0, 'paidInstallments' => 0])->assertUnprocessable()->assertJsonValidationErrors(['totalInstallments']);
        $this->mkInst($c, ['initialBalance' => 'abc'])->assertUnprocessable()->assertJsonPath('errors.initialBalance.0', 'Monto inválido');
        $this->mkInst($c, ['initialBalance' => '0'])->assertUnprocessable()->assertJsonValidationErrors(['initialBalance']);
        $this->mkInst($c, ['initialBalance' => '1.000.000.000.000'])->assertUnprocessable()->assertJsonPath('errors.initialBalance.0', 'Monto inválido');
        $this->mkInst($c, ['initialBalance' => '999.999.999.999,99'])->assertCreated();
        $this->mkInst(9999)->assertNotFound();
        $this->assertSame(1, InstallmentModel::count());
    }

    /**
     * A duplicate card name is a 422 on the name field.
     */
    public function test_rejects_duplicate_card_name(): void
    {
        $this->mkCard();
        $this->postJson('/cards/store', ['name' => ' Visa '])->assertUnprocessable()->assertJsonPath('errors.name.0', 'Ya existe una tarjeta con ese nombre');
        $this->postJson('/cards/store', ['name' => ' '])->assertUnprocessable()->assertJsonValidationErrors(['name']);
    }

    /**
     * Update is partial and validates the merged result; toggle and delete work and 404 afterwards.
     */
    public function test_update_toggle_cancel_soon_and_delete(): void
    {
        $i = $this->mkInst($this->mkCard())->assertCreated()->json('installment');

        $this->putJson("/installments/{$i['id']}/update", ['paidInstallments' => 24])->assertOk()->assertJsonPath('installment.status', 'terminada');
        $this->putJson("/installments/{$i['id']}/update", ['paidInstallments' => 99])->assertUnprocessable()->assertJsonValidationErrors(['paidInstallments']);
        $this->putJson("/installments/{$i['id']}/update", ['totalInstallments' => 10])->assertUnprocessable()->assertJsonValidationErrors(['paidInstallments']);
        $this->putJson("/installments/{$i['id']}/update", ['type' => 'minicuotas'])->assertUnprocessable()->assertJsonValidationErrors(['installmentAmount']);
        $this->putJson('/installments/999/update', ['item' => 'x'])->assertNotFound();

        $this->postJson("/installments/{$i['id']}/toggle-cancel-soon")->assertOk()->assertJsonPath('installment.cancelSoon', true);
        $this->deleteJson("/installments/{$i['id']}/delete")->assertOk();
        $this->assertSame([], $this->firstCard()['installments']);
        $this->deleteJson("/installments/{$i['id']}/delete")->assertNotFound();
        $this->postJson("/installments/{$i['id']}/toggle-cancel-soon")->assertNotFound();
    }

    /**
     * New installments start at the previous month of the app timezone.
     */
    public function test_last_advanced_month_is_previous_month_in_app_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-15 12:00', 'America/Costa_Rica'));
        $this->assertSame('2025-12', $this->mkInst($this->mkCard())->assertCreated()->json('installment.lastAdvancedMonth'));
        Carbon::setTestNow();
    }

    /**
     * Mutations sync the Cuotas line of the open periods in the same transaction.
     */
    public function test_mutations_sync_cuotas_lines(): void
    {
        $c = $this->mkCard();
        $p = $this->makePeriod();
        $line = fn () => $this->cuotasLines($p['id'])->sole()->estimated;

        $i = $this->mkInst($c, ['item' => 'Reloj'])->assertCreated()->json('installment');
        $this->assertSame('5521.31', $line());
        $this->putJson("/installments/{$i['id']}/update", ['paidInstallments' => 24])->assertOk();
        $this->assertSame('0.00', $line());
        $this->putJson("/installments/{$i['id']}/update", ['paidInstallments' => 12])->assertOk();
        $this->deleteJson("/installments/{$i['id']}/delete")->assertOk();
        $this->assertSame('0.00', $line());
    }

    /**
     * A mutation does not touch periods older than the latest I quincena month (II Nov unaffected after I Dic exists).
     */
    public function test_sync_scope_leaves_older_month_untouched(): void
    {
        $c = $this->mkCard();
        $this->mkInst($c, ['item' => 'Reloj'])->assertCreated();
        $nov2 = $this->makePeriod(['year' => 2026, 'month' => 11, 'half' => 2]);
        $dic1 = $this->makePeriod(['year' => 2026, 'month' => 12, 'half' => 1]);
        $this->assertSame('5521.30', $this->cuotasLines($nov2['id'])->sole()->estimated);

        $this->mkInst($c, ['item' => 'Wallet', 'totalInstallments' => 18, 'paidInstallments' => 17, 'initialBalance' => '18.000'])->assertCreated();

        $this->assertSame('5521.30', $this->cuotasLines($nov2['id'])->sole()->estimated);
        $this->assertSame('6021.31', $this->cuotasLines($dic1['id'])->sole()->estimated);
    }
}
