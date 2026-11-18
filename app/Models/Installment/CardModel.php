<?php

namespace App\Models\Installment;

use App\Models\Period\LineModel;
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
     * The attributes that are mass assignable .
     *
     * @var list<string>
     */
    protected $fillable = ['name'];

    /**
     * Lines that refer to this card.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(LineModel::class, 'card_id');
    }

    /**
     * Installments charged to this card.
     */
    public function installments(): HasMany
    {
        return $this->hasMany(InstallmentModel::class, 'card_id');
    }
}
