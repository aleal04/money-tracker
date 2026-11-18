<?php

namespace App\DTOs\Installment;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Request;
use App\Helpers\PeriodKeyHelper;

class InstallmentStoreDto
{
    /**
     * Create the installment fields to store; money is in cents.
     *
     * @param  int  $cardId  The card the installment is charged to.
     * @param  string  $merchant  The trimmed merchant.
     * @param  string  $item  The trimmed item.
     * @param  int  $totalInstallments  The total cuotas.
     * @param  int  $paidInstallments  The paid cuotas.
     * @param  string  $type  The installment type.
     * @param  int  $initialBalance  The initial balance in cents.
     * @param  int|null  $installmentAmount  The cuota amount in cents (required for minicuotas).
     * @param  int|null  $balanceOverride  The manual outstanding balance in cents, or null.
     */
    public function __construct(
        public readonly int $cardId,
        public readonly string $merchant,
        public readonly string $item,
        public readonly int $totalInstallments,
        public readonly int $paidInstallments,
        public readonly string $type,
        public readonly int $initialBalance,
        public readonly ?int $installmentAmount,
        public readonly ?int $balanceOverride,
    ) {}

    /**
     * Create an InstallmentStoreDto from a validated request.
     *
     * @param  Request  $request  The HTTP request containing the installment data.
     * @return InstallmentStoreDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        $amount = $request->input('installmentAmount');
        $override = $request->input('balanceOverride');

        return new self(
            cardId: (int) $request->input('cardId'),
            merchant: trim((string) $request->input('merchant')),
            item: trim((string) $request->input('item')),
            totalInstallments: (int) $request->input('totalInstallments'),
            paidInstallments: (int) $request->input('paidInstallments'),
            type: (string) $request->input('type'),
            initialBalance: (int) MoneyHelper::parseAmount((string) $request->input('initialBalance')),
            installmentAmount: $amount === null ? null : MoneyHelper::parseAmount((string) $amount),
            balanceOverride: $override === null ? null : MoneyHelper::parseAmount((string) $override),
        );
    }

    /**
     * Convert the DTO to the installment columns; last_advanced_month is the previous month in the app timezone.
     *
     * @return array<string, mixed> The installment columns.
     */
    public function toArray(): array
    {
        return [
            'FkCard' => $this->cardId,
            'Merchant' => $this->merchant,
            'Item' => $this->item,
            'TotalInstallments' => $this->totalInstallments,
            'PaidInstallments' => $this->paidInstallments,
            'Type' => $this->type,
            'InitialBalance' => MoneyHelper::fromCents($this->initialBalance),
            'InstallmentAmount' => $this->installmentAmount === null ? null : MoneyHelper::fromCents($this->installmentAmount),
            'BalanceOverride' => $this->balanceOverride === null ? null : MoneyHelper::fromCents($this->balanceOverride),
            'LastAdvancedMonth' => PeriodKeyHelper::prevMonthKey(now()->year, now()->month),
        ];
    }
}
