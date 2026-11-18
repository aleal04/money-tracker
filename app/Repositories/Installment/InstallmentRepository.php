<?php

namespace App\Repositories\Installment;

use Closure;
use Illuminate\Support\Facades\DB;
use App\Models\Installment\CardModel;
use App\DTOs\Installment\CardStoreDto;
use App\Models\Installment\InstallmentModel;
use App\Repositories\Concerns\RunsTransactionStepsTrait;

class InstallmentRepository
{
    use RunsTransactionStepsTrait;

    /**
     * Run the steps of an installment write in a single transaction.
     *
     * @param  Closure  $steps  The steps; they return the success or the failed response.
     * @param  string  $failureMessage  The message when an unexpected error stops the transaction.
     * @return object The steps response or the generic error.
     */
    public function transaction(Closure $steps, string $failureMessage): object
    {
        return $this->runTransactionSteps(DB::connection(), $steps, $failureMessage, ['statusCode' => 500]);
    }

    /**
     * Store a card.
     *
     * @param  CardStoreDto  $cardData  The card fields.
     * @param  int  $spaceId  The resolved space identifier.
     * @return object The response with the card, or a 500 error.
     */
    public function storeCard(CardStoreDto $cardData, int $spaceId): object
    {
        return $this->transaction(function () use ($cardData, $spaceId): object {
            $card = CardModel::query()->create($cardData->toArray() + ['FkSpace' => $spaceId]);

            return createValidationSuccessResponse('Tarjeta creada correctamente.', ['card' => ['id' => $card->IdCard, 'name' => $card->Name]]);
        }, 'No se pudo crear la tarjeta.');
    }

    /**
     * Insert an installment; to run inside a transaction.
     *
     * @param  array<string, mixed>  $attributes  The installment columns.
     * @return InstallmentModel The created installment.
     */
    public function insert(array $attributes): InstallmentModel
    {
        return InstallmentModel::query()->create($attributes);
    }

    /**
     * Update an installment; to run inside a transaction.
     *
     * @param  InstallmentModel  $installment  The locked installment.
     * @param  array<string, mixed>  $changes  The changed columns.
     */
    public function update(InstallmentModel $installment, array $changes): void
    {
        $installment->update($changes);
    }

    /**
     * Logically delete an installment; to run inside a transaction.
     *
     * @param  InstallmentModel  $installment  The locked installment.
     */
    public function delete(InstallmentModel $installment): void
    {
        $installment->logicalDelete();
    }
}
