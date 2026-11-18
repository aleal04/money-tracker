<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;

class TemplateModel extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'templates';

    /**
     * The attributes that are mass assignable (money columns stay decimal strings).
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'category', 'estimated', 'frequency', 'half', 'active'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'half' => 'integer',
        'estimated' => 'decimal:2',
        'active' => 'boolean',
    ];
}
