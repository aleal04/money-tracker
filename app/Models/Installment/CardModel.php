<?php

namespace App\Models\Installment;

use App\Models\Period\LineModel;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogicalDeleteTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardModel extends Model
{
    use LogicalDeleteTrait;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'Card';

    /**
     * The name of the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'IdCard';

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
    protected $fillable = ['Name'];

    /**
     * Lines that refer to this card.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(LineModel::class, 'FkCard', 'IdCard');
    }

    /**
     * Installments charged to this card.
     */
    public function installments(): HasMany
    {
        return $this->hasMany(InstallmentModel::class, 'FkCard', 'IdCard');
    }
}
