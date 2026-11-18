<?php

namespace App\Contracts\Controllers\Space;

use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Space\StoreSpaceFormRequest;
use App\Http\Requests\Space\UpdateSpaceFormRequest;

interface SpaceControllerContract
{
    /**
     * Display the spaces page.
     *
     * @return Response The spaces page.
     */
    public function index(): Response;

    /**
     * The spaces as JSON.
     *
     * @return JsonResponse The space views.
     */
    public function spaceList(): JsonResponse;

    /**
     * Store a space.
     *
     * @param  StoreSpaceFormRequest  $requestData  The validated space fields.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StoreSpaceFormRequest $requestData): JsonResponse;

    /**
     * Update the sent fields of a space.
     *
     * @param  UpdateSpaceFormRequest  $requestData  The validated space fields.
     * @return JsonResponse The result of the update operation.
     */
    public function update(UpdateSpaceFormRequest $requestData): JsonResponse;

    /**
     * Delete a space that is empty and not the primary one.
     *
     * @param  int  $spaceId  The space identifier.
     * @return JsonResponse The result of the delete operation.
     */
    public function destroy(int $spaceId): JsonResponse;

    /**
     * Make a space the primary one.
     *
     * @param  int  $spaceId  The space identifier.
     * @return JsonResponse The result of the operation.
     */
    public function makePrimary(int $spaceId): JsonResponse;
}
