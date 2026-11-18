<?php

namespace App\Models\Period;

use App\Helpers\MoneyHelper;
use App\Models\Installment\CardModel;
use App\Models\Template\TemplateModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineModel extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'lines';

    /**
     * The attributes that are mass assignable (money columns stay decimal strings).
     *
     * @var list<string>
     */
    protected $fillable = ['period_id', 'category', 'name', 'estimated', 'actual', 'paid', 'paid_at', 'source', 'template_id', 'card_id', 'carried_to_line_id', 'sort'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'estimated' => 'decimal:2',
        'actual' => 'decimal:2',
        'paid' => 'boolean',
        'paid_at' => 'datetime',
        'sort' => 'integer',
    ];

    /**
     * The line this one was carried to.
     */
    public function carriedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'carried_to_line_id');
    }

    /**
     * Lines carried into this one.
     */
    public function carriedFrom(): HasMany
    {
        return $this->hasMany(self::class, 'carried_to_line_id');
    }

    /**
     * The template this line was generated from.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateModel::class, 'template_id');
    }

    /**
     * The card this line refers to.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(CardModel::class, 'card_id');
    }

    /**
     * The period this line belongs to.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PeriodModel::class, 'period_id');
    }

    /**
     * The stable JSON shape of the line: money in cents, plus the carried flag.
     *
     * @return array{id:int,periodId:int,category:string,name:string,estimated:int,actual:?int,paid:bool,paidAt:?string,source:string,templateId:?int,cardId:?int,carriedToLineId:?int,sort:int,carried:bool}
     */
    public function toView(): array
    {
        return [
            'id' => $this->id,
            'periodId' => $this->period_id,
            'category' => $this->category,
            'name' => $this->name,
            'estimated' => (int) MoneyHelper::toCents($this->estimated),
            'actual' => MoneyHelper::toCents($this->actual),
            'paid' => (bool) $this->paid,
            'paidAt' => $this->paid_at?->toISOString(),
            'source' => $this->source,
            'templateId' => $this->template_id,
            'cardId' => $this->card_id,
            'carriedToLineId' => $this->carried_to_line_id,
            'sort' => $this->sort,
            'carried' => $this->carried_to_line_id !== null,
        ];
    }
}
