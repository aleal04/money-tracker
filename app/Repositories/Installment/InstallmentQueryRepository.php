<?php

namespace App\Repositories\Installment;

use Illuminate\Support\Collection;
use App\Models\Installment\CardModel;
use App\Repositories\BaseQueryRepository;
use App\Models\Installment\InstallmentModel;

class InstallmentQueryRepository extends BaseQueryRepository
{
    /**
     * Find a card by identifier.
     *
     * @param  int  $cardId  The card identifier.
     * @return CardModel|null The card, or null when not found.
     */
    public function findCard(int $cardId): ?CardModel
    {
        return CardModel::query()->find($cardId);
    }

    /**
     * Find an installment by identifier, optionally locking its row for the current transaction.
     *
     * @param  int  $installmentId  The installment identifier.
     * @param  bool  $lock  Whether to lock the row (FOR UPDATE).
     * @return InstallmentModel|null The installment, or null when not found.
     */
    public function findInstallment(int $installmentId, bool $lock = false): ?InstallmentModel
    {
        $query = InstallmentModel::query();

        return ($lock ? $query->lockForUpdate() : $query)->find($installmentId);
    }

    /**
     * Every card in creation order.
     *
     * @return Collection<int, CardModel> The cards.
     */
    public function listCards(): Collection
    {
        return CardModel::query()->orderBy('id')->get();
    }

    /**
     * Every installment in creation order.
     *
     * @return Collection<int, InstallmentModel> The installments.
     */
    public function listInstallments(): Collection
    {
        return InstallmentModel::query()->orderBy('id')->get();
    }
}
