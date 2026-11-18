<?php

namespace App\Contracts\Controllers\Period;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\Period\StoreLineFormRequest;
use App\Http\Requests\Period\UpdateLineFormRequest;

interface LineControllerContract
{
    /**
     * Store a manual line in a period.
     *
     * @param  StoreLineFormRequest  $requestData  The validated line fields.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StoreLineFormRequest $requestData): JsonResponse;

    /**
     * Update the sent fields of a line.
     *
     * @param  UpdateLineFormRequest  $requestData  The validated line fields.
     * @return JsonResponse The result of the update operation.
     */
    public function update(UpdateLineFormRequest $requestData): JsonResponse;

    /**
     * Toggle the paid flag of a line.
     *
     * @param  int  $lineId  The line identifier.
     * @return JsonResponse The result of the toggle operation.
     */
    public function togglePaid(int $lineId): JsonResponse;

    /**
     * Delete a line.
     *
     * @param  int  $lineId  The line identifier.
     * @return JsonResponse The result of the delete operation.
     */
    public function destroy(int $lineId): JsonResponse;

    /**
     * The original lines carried into an arrastre line.
     *
     * @param  int  $lineId  The arrastre line identifier.
     * @return JsonResponse The line views.
     */
    public function arrastreDetail(int $lineId): JsonResponse;
}
