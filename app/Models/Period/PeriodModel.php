<?php

namespace App\Models\Period;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodModel extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'periods';

    /**
     * The attributes that are mass assignable (money columns stay decimal strings).
     *
     * @var list<string>
     */
    protected $fillable = ['year', 'month', 'half', 'closed'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'half' => 'integer',
        'closed' => 'boolean',
    ];

    /**
     * Lines of this period.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(LineModel::class, 'period_id');
    }
}
