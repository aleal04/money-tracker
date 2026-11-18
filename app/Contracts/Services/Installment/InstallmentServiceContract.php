<?php

namespace App\Contracts\Services\Installment;

use App\DTOs\Installment\CardStoreDto;
use App\DTOs\Installment\InstallmentStoreDto;
use App\DTOs\Installment\InstallmentUpdateDto;

interface InstallmentServiceContract
{
    /**
     * The cards with their installments and totals (terminadas excluded from the totals).
     *
     * @return array<int, array<string, mixed>> The card views.
     */
    public function cardList(): array;

    /**
     * Store a card.
     *
     * @param  CardStoreDto  $cardData  The card fields.
     * @return object The result of the store operation.
     */
    public function storeCard(CardStoreDto $cardData): object;

    /**
     * Store an installment and sync the cuotas lines in the same transaction.
     *
     * @param  InstallmentStoreDto  $installmentData  The installment fields.
     * @return object The result of the store operation.
     */
    public function storeInstallment(InstallmentStoreDto $installmentData): object;

    /**
     * Update an installment and sync the cuotas lines in the same transaction.
     *
     * @param  InstallmentUpdateDto  $installmentData  The installment identifier and changed fields.
     * @return object The result of the update operation.
     */
    public function updateInstallment(InstallmentUpdateDto $installmentData): object;

    /**
     * Delete an installment and sync the cuotas lines in the same transaction.
     *
     * @param  int  $installmentId  The installment identifier.
     * @return object The result of the delete operation.
     */
    public function deleteInstallment(int $installmentId): object;

    /**
     * Toggle the cancel-soon flag of an installment.
     *
     * @param  int  $installmentId  The installment identifier.
     * @return object The result of the toggle operation.
     */
    public function toggleCancelSoon(int $installmentId): object;
}
