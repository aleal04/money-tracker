<?php

namespace App\Models\Period;

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
}
