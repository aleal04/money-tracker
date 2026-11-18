<?php

namespace App\Models\Period;

use App\Helpers\MoneyHelper;
use App\Models\Installment\CardModel;
use App\Models\Template\TemplateModel;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogicalDeleteTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineModel extends Model
{
    use LogicalDeleteTrait;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'Line';

    /**
     * The name of the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'IdLine';

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
    protected $fillable = ['FkPeriod', 'Category', 'Name', 'Estimated', 'Actual', 'Paid', 'PaidAt', 'Source', 'FkTemplate', 'FkCard', 'FkCarriedToLine', 'FkTargetSpace', 'FkTransferFromLine', 'Sort'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'Estimated' => 'decimal:2',
        'Actual' => 'decimal:2',
        'Paid' => 'boolean',
        'PaidAt' => 'datetime',
        'Sort' => 'integer',
        'IsDeleted' => 'boolean',
    ];

    /**
     * The line this one was carried to.
     */
    public function carriedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'FkCarriedToLine', 'IdLine');
    }

    /**
     * Lines carried into this one.
     */
    public function carriedFrom(): HasMany
    {
        return $this->hasMany(self::class, 'FkCarriedToLine', 'IdLine');
    }

    /**
     * The template this line was generated from.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateModel::class, 'FkTemplate', 'IdTemplate');
    }

    /**
     * The card this line refers to.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(CardModel::class, 'FkCard', 'IdCard');
    }

    /**
     * The period this line belongs to.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PeriodModel::class, 'FkPeriod', 'IdPeriod');
    }

    /**
     * The stable JSON shape of the line: money in cents, plus the carried flag.
     *
     * @return array{id:int,periodId:int,category:string,name:string,estimated:int,actual:?int,paid:bool,paidAt:?string,source:string,templateId:?int,cardId:?int,carriedToLineId:?int,sort:int,carried:bool}
     */
    public function toView(): array
    {
        return [
            'id' => $this->IdLine,
            'periodId' => $this->FkPeriod,
            'category' => $this->Category,
            'name' => $this->Name,
            'estimated' => (int) MoneyHelper::toCents($this->Estimated),
            'actual' => MoneyHelper::toCents($this->Actual),
            'paid' => (bool) $this->Paid,
            'paidAt' => $this->PaidAt?->toISOString(),
            'source' => $this->Source,
            'templateId' => $this->FkTemplate,
            'cardId' => $this->FkCard,
            'carriedToLineId' => $this->FkCarriedToLine,
            'sort' => $this->Sort,
            'carried' => $this->FkCarriedToLine !== null,
        ];
    }
}
