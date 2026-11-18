<?php

namespace App\Models\Installment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardModel extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'cards';

    /**
     * The attributes that are mass assignable (money columns stay decimal strings).
     *
     * @var list<string>
     */
    protected $fillable = ['name'];

    /**
     * Installments charged to this card.
     */
    public function installments(): HasMany
    {
        return $this->hasMany(InstallmentModel::class, 'card_id');
    }
}
