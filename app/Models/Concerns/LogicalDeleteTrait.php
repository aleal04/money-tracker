<?php

namespace App\Models\Concerns;

use App\Constant\GeneralConstant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Logical delete with the ssi audit columns: rows are never removed, IsDeleted and DeletedAt are set instead.
 * A global scope hides the deleted rows from every query of the model (find, lists, locks and mass updates).
 *
 * @mixin Model
 */
trait LogicalDeleteTrait
{
    /**
     * Register the global scope that leaves out the deleted rows.
     */
    protected static function bootLogicalDeleteTrait(): void
    {
        static::addGlobalScope('notDeleted', function (Builder $query): void {
            $query->where($query->qualifyColumn('IsDeleted'), GeneralConstant::NOT_DELETED);
        });
    }

    /**
     * Mark the row as deleted (IsDeleted = 1, DeletedAt = now) instead of removing it.
     */
    public function logicalDelete(): void
    {
        $this->forceFill(['IsDeleted' => GeneralConstant::DELETED, 'DeletedAt' => now()])->save();
    }
}
