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
     * Create a period of the primary space through the HTTP endpoint and return its JSON view.
     *
     * @param  array{startDate:string,endDate:string}|null  $dates  The period dates; I Quincena Noviembre 2026 by default.
     * @return array<string, mixed> The period view.
     */
    protected function makePeriod(?array $dates = null): array
    {
        return $this->postJson('/periods/store', $dates ?? $this->dates(11, 1))->assertCreated()->json('period');
    }

    /**
     * Seed the three recurring templates of the NestJS tests (CONAPE was "mensual en I quincena": day 1).
     */
    protected function seedTemplates(): void
    {
        foreach ([['Kolbi', 1086500, 'cada_periodo', null], ['Banco Popular', 24000000, 'mensual_repartido', null], ['CONAPE', 6846000, 'mensual_dia', 1]] as [$name, $cents, $frequency, $day]) {
            TemplateModel::create([
                'FkSpace' => $this->primarySpaceId(), 'Name' => $name, 'Category' => 'deuda_fija', 'Estimated' => MoneyHelper::fromCents($cents),
                'Frequency' => $frequency, 'DayOfMonth' => $day, 'Active' => true,
            ]);
        }
    }

    /**
     * Create the Reloj tasa0 installment (cuota 1104261 cents, 12 of 24 paid) on a new card.
     *
     * @return CardModel The card.
     */
    protected function seedReloj(): CardModel
    {
        $card = CardModel::create(['FkSpace' => $this->primarySpaceId(), 'Name' => 'TC BAC']);
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
            'FkCard' => $card->IdCard, 'Merchant' => $name, 'Item' => $name, 'Type' => $type, 'TotalInstallments' => $total,
            'PaidInstallments' => $paid, 'InitialBalance' => MoneyHelper::fromCents($initialCents),
            'InstallmentAmount' => $amountCents === null ? null : MoneyHelper::fromCents($amountCents), 'LastAdvancedMonth' => $lastAdvanced,
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
        return LineModel::where(['FkPeriod' => $periodId, 'Source' => 'installments'])->get();
    }

    /**
     * The period model of a 2026 quincena of the primary space.
     *
     * @param  int  $month  The month.
     * @param  int  $half  The half.
     * @return PeriodModel The period.
     */
    protected function period(int $month, int $half): PeriodModel
    {
        return PeriodModel::where(['FkSpace' => $this->primarySpaceId(), 'StartDate' => $this->dates($month, $half)['startDate']])->firstOrFail();
    }
}
