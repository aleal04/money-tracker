<?php

namespace App\Models\Space;

use App\Models\Period\PeriodModel;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogicalDeleteTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpaceModel extends Model
{
    use LogicalDeleteTrait;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'Space';

    /**
     * The name of the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'IdSpace';

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
    protected $fillable = ['Name', 'Frequency', 'CustomDays', 'InitialBalance', 'IsPrimary', 'Sort'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'CustomDays' => 'integer',
        'InitialBalance' => 'decimal:2',
        'IsPrimary' => 'boolean',
        'Sort' => 'integer',
        'IsDeleted' => 'boolean',
    ];

    /**
     * Periods of this space.
     */
    public function periods(): HasMany
    {
        return $this->hasMany(PeriodModel::class, 'FkSpace', 'IdSpace');
    }
}
