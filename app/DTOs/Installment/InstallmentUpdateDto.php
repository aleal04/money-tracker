<?php

namespace App\DTOs\Installment;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Request;
use App\Constant\InstallmentTypeConstant;

class InstallmentUpdateDto
{
    /**
     * Create the partial installment update; only the fields sent are changed.
     *
     * @param  int  $installmentId  The installment identifier.
     * @param  int|null  $cardId  The new card, or null when not sent.
     * @param  string|null  $merchant  The new trimmed merchant, or null when not sent.
     * @param  string|null  $item  The new trimmed item, or null when not sent.
     * @param  int|null  $totalInstallments  The new total cuotas, or null when not sent.
     * @param  int|null  $paidInstallments  The new paid cuotas, or null when not sent.
     * @param  string|null  $type  The new type, or null when not sent.
     * @param  int|null  $initialBalance  The new initial balance in cents, or null when not sent.
     * @param  bool  $hasInstallmentAmount  Whether installmentAmount was sent (it may be null to clear it).
     * @param  int|null  $installmentAmount  The new cuota amount in cents.
     * @param  bool  $hasBalanceOverride  Whether balanceOverride was sent (it may be null to clear it).
     * @param  int|null  $balanceOverride  The new manual balance in cents.
     */
    public function __construct(
        public readonly int $installmentId,
        public readonly ?int $cardId,
        public readonly ?string $merchant,
        public readonly ?string $item,
        public readonly ?int $totalInstallments,
        public readonly ?int $paidInstallments,
        public readonly ?string $type,
        public readonly ?int $initialBalance,
        public readonly bool $hasInstallmentAmount,
        public readonly ?int $installmentAmount,
        public readonly bool $hasBalanceOverride,
        public readonly ?int $balanceOverride,
    ) {}

    /**
     * Create an InstallmentUpdateDto from a validated request; the installment comes from the route.
     *
     * @param  Request  $request  The HTTP request containing the changed fields.
     * @return InstallmentUpdateDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        $amount = $request->input('installmentAmount');
        $override = $request->input('balanceOverride');
        $int = fn (string $key) => $request->has($key) ? (int) $request->input($key) : null;
        $text = fn (string $key) => $request->has($key) ? trim((string) $request->input($key)) : null;

        return new self(
            installmentId: (int) $request->route('installmentId'),
            cardId: $int('cardId'),
            merchant: $text('merchant'),
            item: $text('item'),
            totalInstallments: $int('totalInstallments'),
            paidInstallments: $int('paidInstallments'),
            type: $request->has('type') ? (string) $request->input('type') : null,
            initialBalance: $request->has('initialBalance') ? (int) MoneyHelper::parseAmount((string) $request->input('initialBalance')) : null,
            hasInstallmentAmount: $request->has('installmentAmount'),
            installmentAmount: $amount === null ? null : MoneyHelper::parseAmount((string) $amount),
            hasBalanceOverride: $request->has('balanceOverride'),
            balanceOverride: $override === null ? null : MoneyHelper::parseAmount((string) $override),
        );
    }

    /**
     * Convert the DTO to the installment columns that changed (money as decimal strings).
     *
     * @return array<string, mixed> The changed installment columns.
     */
    public function toArray(): array
    {
        $changes = array_filter([
            'card_id' => $this->cardId,
            'merchant' => $this->merchant,
            'item' => $this->item,
            'total_installments' => $this->totalInstallments,
            'paid_installments' => $this->paidInstallments,
            'type' => $this->type,
            'initial_balance' => $this->initialBalance === null ? null : MoneyHelper::fromCents($this->initialBalance),
        ], fn ($value) => $value !== null);

        if ($this->type === InstallmentTypeConstant::TASA0) {
            $changes['installment_amount'] = null; // tasa0 derives its cuota from the balance.
        } elseif ($this->hasInstallmentAmount) {
            $changes['installment_amount'] = $this->installmentAmount === null ? null : MoneyHelper::fromCents($this->installmentAmount);
        }
        if ($this->hasBalanceOverride) {
            $changes['balance_override'] = $this->balanceOverride === null ? null : MoneyHelper::fromCents($this->balanceOverride);
        }

        return $changes;
    }
}
