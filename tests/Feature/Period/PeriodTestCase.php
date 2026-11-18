<?php

namespace Tests\Feature\Period;

use Tests\TestCase;
use App\Helpers\MoneyHelper;
use App\Models\Period\LineModel;
use App\Models\Period\PeriodModel;
use Illuminate\Support\Collection;
use App\Models\Installment\CardModel;
use App\Models\Template\TemplateModel;
use App\Models\Installment\InstallmentModel;

abstract class PeriodTestCase extends TestCase
{
    /**
     * Create a period through the HTTP endpoint and return its JSON view.
     *
     * @param  array<string, int>  $key  The period key.
     * @return array<string, mixed> The period view.
     */
    protected function makePeriod(array $key = ['year' => 2026, 'month' => 11, 'half' => 1]): array
    {
        return $this->postJson('/periods/store', $key)->assertCreated()->json('period');
    }

    /**
     * Seed the three recurring templates of the NestJS tests.
     */
    protected function seedTemplates(): void
    {
        foreach ([['Kolbi', 1086500, 'quincenal', null], ['Banco Popular', 24000000, 'mensual_dividido', null], ['CONAPE', 6846000, 'mensual_en_quincena', 1]] as [$name, $cents, $frequency, $half]) {
            TemplateModel::create(['name' => $name, 'category' => 'deuda_fija', 'estimated' => MoneyHelper::fromCents($cents), 'frequency' => $frequency, 'half' => $half, 'active' => true]);
        }
    }

    /**
     * Create the Reloj tasa0 installment (cuota 1104261 cents, 12 of 24 paid) on a new card.
     *
     * @return CardModel The card.
     */
    protected function seedReloj(): CardModel
    {
        $card = CardModel::create(['name' => 'TC BAC']);
        $this->seedInstallment($card, 'Reloj', 'tasa0', 24, 12, 1104261 * 24, null);

        return $card;
    }

    /**
     * Create an installment.
     *
     * @param  CardModel  $card  The card.
     * @param  string  $name  Merchant and item.
     * @param  string  $type  The installment type.
     * @param  int  $total  Total cuotas.
     * @param  int  $paid  Paid cuotas.
     * @param  int  $initialCents  Initial balance in cents.
     * @param  int|null  $amountCents  Cuota amount in cents (minicuotas).
     * @param  string  $lastAdvanced  The last advanced month.
     * @return InstallmentModel The installment.
     */
    protected function seedInstallment(CardModel $card, string $name, string $type, int $total, int $paid, int $initialCents, ?int $amountCents, string $lastAdvanced = '2026-10'): InstallmentModel
    {
        return InstallmentModel::create([
            'card_id' => $card->id, 'merchant' => $name, 'item' => $name, 'type' => $type, 'total_installments' => $total,
            'paid_installments' => $paid, 'initial_balance' => MoneyHelper::fromCents($initialCents),
            'installment_amount' => $amountCents === null ? null : MoneyHelper::fromCents($amountCents), 'last_advanced_month' => $lastAdvanced,
        ]);
    }

    /**
     * Create the Wallet minicuotas installment (cuota 251460 cents, 17 of 18 paid).
     *
     * @param  CardModel  $card  The card.
     * @param  string  $name  Merchant and item.
     * @return InstallmentModel The installment.
     */
    protected function seedWallet(CardModel $card, string $name = 'Wallet'): InstallmentModel
    {
        return $this->seedInstallment($card, $name, 'minicuotas', 18, 17, 251460 * 18, 251460);
    }

    /**
     * The installments lines of a period.
     *
     * @param  int  $periodId  The period identifier.
     * @return Collection<int, LineModel> The lines.
     */
    protected function cuotasLines(int $periodId)
    {
        return LineModel::where(['period_id' => $periodId, 'source' => 'installments'])->get();
    }

    /**
     * The period model by key.
     *
     * @param  int  $month  The month.
     * @param  int  $half  The half.
     * @return PeriodModel The period.
     */
    protected function period(int $month, int $half): PeriodModel
    {
        return PeriodModel::where(['year' => 2026, 'month' => $month, 'half' => $half])->firstOrFail();
    }
}
