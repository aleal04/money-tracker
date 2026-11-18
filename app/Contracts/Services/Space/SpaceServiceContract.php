<?php

namespace App\Contracts\Services\Space;

use App\DTOs\Space\SpaceStoreDto;
use App\DTOs\Space\SpaceUpdateDto;

interface SpaceServiceContract
{
    /**
     * Every space in creation order.
     *
     * @return array<int, array<string, mixed>> The space views.
     */
    public function spaceList(): array;

    /**
     * Store a space.
     *
     * @param  SpaceStoreDto  $spaceData  The space fields.
     * @return object The result of the store operation.
     */
    public function storeSpace(SpaceStoreDto $spaceData): object;

    /**
     * Update a space.
     *
     * @param  SpaceUpdateDto  $spaceData  The space identifier and changed fields.
     * @return object The result of the update operation.
     */
    public function updateSpace(SpaceUpdateDto $spaceData): object;

    /**
     * Delete a space.
     *
     * @param  int  $spaceId  The space identifier.
     * @return object The result of the delete operation.
     */
    public function deleteSpace(int $spaceId): object;

    /**
     * Make a space the primary one.
     *
     * @param  int  $spaceId  The space identifier.
     * @return object The result of the operation.
     */
    public function makePrimary(int $spaceId): object;
}
