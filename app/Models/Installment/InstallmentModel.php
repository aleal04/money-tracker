<?php

namespace App\Models\Installment;

use App\Helpers\MoneyHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentModel extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'installments';

    /**
     * The attributes that are mass assignable (money columns stay decimal strings).
     *
     * @var list<string>
     */
    protected $fillable = ['card_id', 'merchant', 'item', 'total_installments', 'paid_installments', 'type', 'initial_balance', 'installment_amount', 'balance_override', 'cancel_soon', 'last_advanced_month'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_installments' => 'integer',
        'paid_installments' => 'integer',
        'initial_balance' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'balance_override' => 'decimal:2',
        'cancel_soon' => 'boolean',
    ];

    /**
     * The card this installment belongs to.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(CardModel::class, 'card_id');
    }

    /**
     * The calculators' camelCase input, with money in cents.
     *
     * @return array<string, mixed> The input.
     */
    public function toInput(): array
    {
        return [
            'id' => $this->id,
            'cardId' => $this->card_id,
            'type' => $this->type,
            'totalInstallments' => $this->total_installments,
            'paidInstallments' => $this->paid_installments,
            'lastAdvancedMonth' => $this->last_advanced_month,
            'initialBalance' => (int) MoneyHelper::toCents($this->initial_balance),
            'installmentAmount' => MoneyHelper::toCents($this->installment_amount),
            'balanceOverride' => MoneyHelper::toCents($this->balance_override),
        ];
    }
}
