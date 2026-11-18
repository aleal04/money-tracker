<?php

namespace App\Models\Installment;

use App\Helpers\MoneyHelper;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogicalDeleteTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentModel extends Model
{
    use LogicalDeleteTrait;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'Installment';

    /**
     * The name of the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'IdInstallment';

    /**
     * The audit column filled by Eloquent when the row is created.
     *
     * @var string
     */
    const CREATED_AT = 'CreatedAt';

    /**
     * The audit column filled by Eloquent when the row is updated.
     *
     * @var string
     */
    const UPDATED_AT = 'UpdatedAt';

    /**
     * The attributes that are mass assignable (money columns stay decimal strings).
     *
     * @var list<string>
     */
    protected $fillable = ['FkCard', 'Merchant', 'Item', 'TotalInstallments', 'PaidInstallments', 'Type', 'InitialBalance', 'InstallmentAmount', 'BalanceOverride', 'CancelSoon', 'LastAdvancedMonth'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'TotalInstallments' => 'integer',
        'PaidInstallments' => 'integer',
        'InitialBalance' => 'decimal:2',
        'InstallmentAmount' => 'decimal:2',
        'BalanceOverride' => 'decimal:2',
        'CancelSoon' => 'boolean',
        'IsDeleted' => 'boolean',
    ];

    /**
     * The card this installment belongs to.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(CardModel::class, 'FkCard', 'IdCard');
    }

    /**
     * The calculators' camelCase input, with money in cents.
     *
     * @return array<string, mixed> The input.
     */
    public function toInput(): array
    {
        return [
            'id' => $this->IdInstallment,
            'cardId' => $this->FkCard,
            'type' => $this->Type,
            'totalInstallments' => $this->TotalInstallments,
            'paidInstallments' => $this->PaidInstallments,
            'lastAdvancedMonth' => $this->LastAdvancedMonth,
            'initialBalance' => (int) MoneyHelper::toCents($this->InitialBalance),
            'installmentAmount' => MoneyHelper::toCents($this->InstallmentAmount),
            'balanceOverride' => MoneyHelper::toCents($this->BalanceOverride),
        ];
    }
}
