<?php

namespace App\Models\Period;

use Illuminate\Database\Eloquent\Model;
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
     * The period this line belongs to.
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PeriodModel::class, 'period_id');
    }
}
