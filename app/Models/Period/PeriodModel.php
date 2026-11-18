<?php

namespace App\Models\Period;

use App\Helpers\PeriodKeyHelper;
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
     * The attributes that are mass assignable .
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

    /**
     * The stable JSON shape of the period, with its label.
     *
     * @return array{id:int,year:int,month:int,half:int,closed:bool,label:string}
     */
    public function toView(): array
    {
        return [
            'id' => $this->id,
            'year' => $this->year,
            'month' => $this->month,
            'half' => $this->half,
            'closed' => $this->closed,
            'label' => PeriodKeyHelper::label($this->year, $this->month, $this->half),
        ];
    }
}
