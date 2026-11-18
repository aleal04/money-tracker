<?php

namespace App\Http\Controllers\Period;

use App\DTOs\Period\LineStoreDto;
use Illuminate\Http\JsonResponse;
use App\DTOs\Period\LineUpdateDto;
use App\Http\Controllers\Controller;
use App\Services\Period\LineService;
use App\Http\Requests\Period\StoreLineFormRequest;
use App\Http\Requests\Period\UpdateLineFormRequest;
use App\Contracts\Controllers\Period\LineControllerContract;

class LineController extends Controller implements LineControllerContract
{
    /**
     * Create the line controller with its service dependency.
     *
     * @param  LineService  $lineService  The service for line operations.
     */
    public function __construct(private readonly LineService $lineService) {}

    /**
     * Store a manual line in a period.
     *
     * @param  StoreLineFormRequest  $requestData  The validated line fields.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StoreLineFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->lineService->storeLine(LineStoreDto::fromRequest($requestData)), 201);
    }

    /**
     * Update the sent fields of a line.
     *
     * @param  UpdateLineFormRequest  $requestData  The validated line fields.
     * @return JsonResponse The result of the update operation.
     */
    public function update(UpdateLineFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->lineService->updateLine(LineUpdateDto::fromRequest($requestData)));
    }

    /**
     * Toggle the paid flag of a line.
     *
     * @param  int  $lineId  The line identifier.
     * @return JsonResponse The result of the toggle operation.
     */
    public function togglePaid(int $lineId): JsonResponse
    {
        return $this->respond($this->lineService->togglePaid($lineId));
    }

    /**
     * Delete a line.
     *
     * @param  int  $lineId  The line identifier.
     * @return JsonResponse The result of the delete operation.
     */
    public function destroy(int $lineId): JsonResponse
    {
        return $this->respond($this->lineService->deleteLine($lineId));
    }

    /**
     * The original lines carried into an arrastre line.
     *
     * @param  int  $lineId  The arrastre line identifier.
     * @return JsonResponse The line views.
     */
    public function arrastreDetail(int $lineId): JsonResponse
    {
        return response()->json($this->lineService->arrastreDetail($lineId));
    }
}
