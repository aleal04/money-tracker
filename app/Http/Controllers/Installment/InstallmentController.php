<?php

namespace App\Http\Controllers\Installment;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\DTOs\Installment\CardStoreDto;
use App\DTOs\Installment\InstallmentStoreDto;
use App\DTOs\Installment\InstallmentUpdateDto;
use App\Services\Installment\InstallmentService;
use App\Http\Requests\Installment\StoreCardFormRequest;
use App\Http\Requests\Installment\StoreInstallmentFormRequest;
use App\Http\Requests\Installment\UpdateInstallmentFormRequest;
use App\Contracts\Controllers\Installment\InstallmentControllerContract;

class InstallmentController extends Controller implements InstallmentControllerContract
{
    /**
     * Create the installment controller with its service dependency.
     *
     * @param  InstallmentService  $installmentService  The service for card and installment operations.
     */
    public function __construct(private readonly InstallmentService $installmentService) {}

    /**
     * Display the installments page.
     *
     * @return Response The installments page.
     */
    public function index(): Response
    {
        return Inertia::render('Installment/Index');
    }

    /**
     * The cards of the space (`?space=`, primary by default) with their installments and totals as JSON.
     *
     * @param  Request  $request  The request with the optional space.
     * @return JsonResponse The card views.
     */
    public function cardList(Request $request): JsonResponse
    {
        return response()->json($this->installmentService->cardList($this->spaceIdInput($request, 'space')));
    }

    /**
     * Store a card.
     *
     * @param  StoreCardFormRequest  $requestData  The validated card name.
     * @return JsonResponse The result of the store operation.
     */
    public function storeCard(StoreCardFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->installmentService->storeCard(CardStoreDto::fromRequest($requestData)), 201);
    }

    /**
     * Store an installment and sync the cuotas lines.
     *
     * @param  StoreInstallmentFormRequest  $requestData  The validated installment fields.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StoreInstallmentFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->installmentService->storeInstallment(InstallmentStoreDto::fromRequest($requestData)), 201);
    }

    /**
     * Update the sent fields of an installment and sync the cuotas lines.
     *
     * @param  UpdateInstallmentFormRequest  $requestData  The validated installment fields.
     * @return JsonResponse The result of the update operation.
     */
    public function update(UpdateInstallmentFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->installmentService->updateInstallment(InstallmentUpdateDto::fromRequest($requestData)));
    }

    /**
     * Delete an installment and sync the cuotas lines.
     *
     * @param  int  $installmentId  The installment identifier.
     * @return JsonResponse The result of the delete operation.
     */
    public function destroy(int $installmentId): JsonResponse
    {
        return $this->respond($this->installmentService->deleteInstallment($installmentId));
    }

    /**
     * Toggle the cancel-soon flag of an installment.
     *
     * @param  int  $installmentId  The installment identifier.
     * @return JsonResponse The result of the toggle operation.
     */
    public function toggleCancelSoon(int $installmentId): JsonResponse
    {
        return $this->respond($this->installmentService->toggleCancelSoon($installmentId));
    }
}
