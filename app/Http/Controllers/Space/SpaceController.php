<?php

namespace App\Http\Controllers\Space;

use Inertia\Inertia;
use Inertia\Response;
use App\DTOs\Space\SpaceStoreDto;
use Illuminate\Http\JsonResponse;
use App\DTOs\Space\SpaceUpdateDto;
use App\Http\Controllers\Controller;
use App\Services\Space\SpaceService;
use App\Http\Requests\Space\StoreSpaceFormRequest;
use App\Http\Requests\Space\UpdateSpaceFormRequest;
use App\Contracts\Controllers\Space\SpaceControllerContract;

class SpaceController extends Controller implements SpaceControllerContract
{
    /**
     * Create the space controller with its service dependency.
     *
     * @param  SpaceService  $spaceService  The service for space operations.
     */
    public function __construct(private readonly SpaceService $spaceService) {}

    /**
     * Display the spaces page.
     *
     * @return Response The spaces page.
     */
    public function index(): Response
    {
        return Inertia::render('Space/Index');
    }

    /**
     * The spaces as JSON.
     *
     * @return JsonResponse The space views.
     */
    public function spaceList(): JsonResponse
    {
        return response()->json($this->spaceService->spaceList());
    }

    /**
     * Store a space.
     *
     * @param  StoreSpaceFormRequest  $requestData  The validated space fields.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StoreSpaceFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->spaceService->storeSpace(SpaceStoreDto::fromRequest($requestData)), 201);
    }

    /**
     * Update the sent fields of a space.
     *
     * @param  UpdateSpaceFormRequest  $requestData  The validated space fields.
     * @return JsonResponse The result of the update operation.
     */
    public function update(UpdateSpaceFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->spaceService->updateSpace(SpaceUpdateDto::fromRequest($requestData)));
    }

    /**
     * Delete a space that is empty and not the primary one.
     *
     * @param  int  $spaceId  The space identifier.
     * @return JsonResponse The result of the delete operation.
     */
    public function destroy(int $spaceId): JsonResponse
    {
        return $this->respond($this->spaceService->deleteSpace($spaceId));
    }

    /**
     * Make a space the primary one.
     *
     * @param  int  $spaceId  The space identifier.
     * @return JsonResponse The result of the operation.
     */
    public function makePrimary(int $spaceId): JsonResponse
    {
        return $this->respond($this->spaceService->makePrimary($spaceId));
    }
}
