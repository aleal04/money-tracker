<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogicalDeleteTrait;

class TemplateModel extends Model
{
    use LogicalDeleteTrait;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'Template';

    /**
     * The name of the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'IdTemplate';

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
    protected $fillable = ['FkSpace', 'FkTargetSpace', 'Name', 'Category', 'Estimated', 'Frequency', 'DayOfMonth', 'Active'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'DayOfMonth' => 'integer',
        'Estimated' => 'decimal:2',
        'Active' => 'boolean',
        'IsDeleted' => 'boolean',
    ];
}
