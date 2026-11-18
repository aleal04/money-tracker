<?php

namespace App\Services\Space;

use App\Helpers\MoneyHelper;
use App\Models\Space\SpaceModel;
use App\DTOs\Space\SpaceStoreDto;
use App\DTOs\Space\SpaceUpdateDto;
use App\Constant\SpaceFrequencyConstant;
use App\Repositories\Space\SpaceRepository;
use App\Repositories\Space\SpaceQueryRepository;
use App\Contracts\Services\Space\SpaceServiceContract;

class SpaceService implements SpaceServiceContract
{
    /**
     * Create the space service with its dependencies.
     *
     * @param  SpaceRepository  $spaceRepository  The space writes.
     * @param  SpaceQueryRepository  $spaceQueryRepository  The space reads.
     */
    public function __construct(
        private readonly SpaceRepository $spaceRepository,
        private readonly SpaceQueryRepository $spaceQueryRepository,
    ) {}

    /**
     * Every space in creation order.
     *
     * @return array<int, array<string, mixed>> The space views.
     */
    public function spaceList(): array
    {
        return $this->spaceQueryRepository->listSpaces()->map(fn (SpaceModel $space) => $this->toView($space))->all();
    }

    /**
     * Store a space; it is primary when asked or when there is no primary yet (the previous one is demoted).
     *
     * @param  SpaceStoreDto  $spaceData  The space fields.
     * @return object The result of the store operation.
     */
    public function storeSpace(SpaceStoreDto $spaceData): object
    {
        return $this->spaceRepository->transaction(function () use ($spaceData): object {
            $space = $this->spaceRepository->insert($spaceData->toArray());
            if ($spaceData->isPrimary || ! $this->spaceQueryRepository->hasPrimary()) {
                $this->spaceRepository->makePrimary($space);
            }

            return createValidationSuccessResponse('Espacio creado correctamente.', ['space' => $this->toView($space->fresh())]);
        }, 'No se pudo crear el espacio.');
    }

    /**
     * Update a space; the days are cleared when the resulting frequency is not custom. The frequency and days cannot
     * change once the space has periods (the cuota and template splits of its periods depend on them).
     *
     * @param  SpaceUpdateDto  $spaceData  The space identifier and changed fields.
     * @return object The result of the update operation.
     */
    public function updateSpace(SpaceUpdateDto $spaceData): object
    {
        return $this->spaceRepository->transaction(function () use ($spaceData): object {
            $space = $this->spaceQueryRepository->findSpace($spaceData->spaceId, true);
            if ($space === null) {
                return createValidationErrorResponse('No se encontró el espacio.', ['statusCode' => 404]);
            }

            $changes = $spaceData->toArray();
            if (($changes['Frequency'] ?? $space->Frequency) !== SpaceFrequencyConstant::CUSTOM) {
                $changes['CustomDays'] = null;
            }
            $reschedules = ($changes['Frequency'] ?? $space->Frequency) !== $space->Frequency
                || (array_key_exists('CustomDays', $changes) && $changes['CustomDays'] !== $space->CustomDays);
            if ($reschedules && $this->spaceQueryRepository->hasPeriods($space->IdSpace)) {
                return createValidationErrorResponse('No se puede cambiar la frecuencia de un espacio que ya tiene periodos', ['statusCode' => 409]);
            }
            $this->spaceRepository->update($space, $changes);

            return createValidationSuccessResponse('Espacio actualizado correctamente.', ['space' => $this->toView($space->fresh())]);
        }, 'No se pudo actualizar el espacio.');
    }

    /**
     * Delete a space: never the primary one, and only when nothing depends on it.
     *
     * @param  int  $spaceId  The space identifier.
     * @return object The result of the delete operation.
     */
    public function deleteSpace(int $spaceId): object
    {
        return $this->spaceRepository->transaction(function () use ($spaceId): object {
            $space = $this->spaceQueryRepository->findSpace($spaceId, true);
            if ($space === null) {
                return createValidationErrorResponse('No se encontró el espacio.', ['statusCode' => 404]);
            }
            if ($space->IsPrimary) {
                return createValidationErrorResponse('No se puede borrar el espacio principal', ['statusCode' => 409]);
            }
            if ($this->spaceQueryRepository->hasData($spaceId)) {
                return createValidationErrorResponse('El espacio tiene datos; no se puede borrar', ['statusCode' => 409]);
            }

            $this->spaceRepository->delete($space);

            return createValidationSuccessResponse('Espacio eliminado correctamente.');
        }, 'No se pudo eliminar el espacio.');
    }

    /**
     * Make a space the primary one (the previous primary is demoted in the same transaction).
     *
     * @param  int  $spaceId  The space identifier.
     * @return object The result of the operation.
     */
    public function makePrimary(int $spaceId): object
    {
        return $this->spaceRepository->transaction(function () use ($spaceId): object {
            $space = $this->spaceQueryRepository->findSpace($spaceId, true);
            if ($space === null) {
                return createValidationErrorResponse('No se encontró el espacio.', ['statusCode' => 404]);
            }
            $this->spaceRepository->makePrimary($space);

            return createValidationSuccessResponse('Espacio principal actualizado.', ['space' => $this->toView($space->fresh())]);
        }, 'No se pudo cambiar el espacio principal.');
    }

    /**
     * The JSON view of a space; initialBalance is in cents, hasPeriods locks its frequency.
     *
     * @param  SpaceModel  $space  The space.
     * @return array<string, mixed> The view.
     */
    private function toView(SpaceModel $space): array
    {
        return [
            'id' => $space->IdSpace,
            'name' => $space->Name,
            'frequency' => $space->Frequency,
            'customDays' => $space->CustomDays,
            'initialBalance' => MoneyHelper::requireCents($space->InitialBalance),
            'isPrimary' => $space->IsPrimary,
            'hasPeriods' => (bool) ($space->periods_exists ?? $this->spaceQueryRepository->hasPeriods($space->IdSpace)),
        ];
    }
}
