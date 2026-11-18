<?php

namespace App\Models\Period;

use App\Helpers\PeriodKeyHelper;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogicalDeleteTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodModel extends Model
{
    use LogicalDeleteTrait;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'Period';

    /**
     * The name of the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'IdPeriod';

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
     * The attributes that are mass assignable .
     *
     * @var list<string>
     */
    protected $fillable = ['Year', 'Month', 'Half', 'Closed'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'Year' => 'integer',
        'Month' => 'integer',
        'Half' => 'integer',
        'Closed' => 'boolean',
        'IsDeleted' => 'boolean',
    ];

    /**
     * Lines of this period.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(LineModel::class, 'FkPeriod', 'IdPeriod');
    }

    /**
     * Map a period key to its table columns.
     *
     * @param  array{year:int,month:int,half:int}  $key  The period key.
     * @return array{Year:int,Month:int,Half:int} The column values.
     */
    public static function keyColumns(array $key): array
    {
        return ['Year' => $key['year'], 'Month' => $key['month'], 'Half' => $key['half']];
    }

    /**
     * The stable JSON shape of the period, with its label.
     *
     * @return array{id:int,year:int,month:int,half:int,closed:bool,label:string}
     */
    public function toView(): array
    {
        return [
            'id' => $this->IdPeriod,
            'year' => $this->Year,
            'month' => $this->Month,
            'half' => $this->Half,
            'closed' => $this->Closed,
            'label' => PeriodKeyHelper::label($this->Year, $this->Month, $this->Half),
        ];
    }
}
