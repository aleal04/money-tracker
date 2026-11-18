<?php

namespace App\Contracts\Controllers\Installment;

use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Installment\StoreCardFormRequest;
use App\Http\Requests\Installment\StoreInstallmentFormRequest;
use App\Http\Requests\Installment\UpdateInstallmentFormRequest;

interface InstallmentControllerContract
{
    /**
     * Display the installments page.
     *
     * @return Response The installments page.
     */
    public function index(): Response;

    /**
     * The cards with their installments and totals as JSON.
     *
     * @return JsonResponse The card views.
     */
    public function cardList(): JsonResponse;

    /**
     * Store a card.
     *
     * @param  StoreCardFormRequest  $requestData  The validated card name.
     * @return JsonResponse The result of the store operation.
     */
    public function storeCard(StoreCardFormRequest $requestData): JsonResponse;

    /**
     * Store an installment and sync the cuotas lines.
     *
     * @param  StoreInstallmentFormRequest  $requestData  The validated installment fields.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StoreInstallmentFormRequest $requestData): JsonResponse;

    /**
     * Update the sent fields of an installment and sync the cuotas lines.
     *
     * @param  UpdateInstallmentFormRequest  $requestData  The validated installment fields.
     * @return JsonResponse The result of the update operation.
     */
    public function update(UpdateInstallmentFormRequest $requestData): JsonResponse;

    /**
     * Delete an installment and sync the cuotas lines.
     *
     * @param  int  $installmentId  The installment identifier.
     * @return JsonResponse The result of the delete operation.
     */
    public function destroy(int $installmentId): JsonResponse;

    /**
     * Toggle the cancel-soon flag of an installment.
     *
     * @param  int  $installmentId  The installment identifier.
     * @return JsonResponse The result of the toggle operation.
     */
    public function toggleCancelSoon(int $installmentId): JsonResponse;
}
