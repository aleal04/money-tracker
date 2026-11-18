<?php

namespace App\Services\Installment;

use App\Helpers\MoneyHelper;
use App\Helpers\PeriodScheduleHelper;
use App\Models\Installment\CardModel;
use App\DTOs\Installment\CardStoreDto;
use App\Services\Period\CuotasSyncService;
use App\Constant\InstallmentStatusConstant;
use App\Models\Installment\InstallmentModel;
use App\DTOs\Installment\InstallmentStoreDto;
use App\DTOs\Installment\InstallmentUpdateDto;
use App\Repositories\Space\SpaceQueryRepository;
use App\Repositories\Installment\InstallmentRepository;
use App\Repositories\Installment\InstallmentQueryRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Contracts\Services\Installment\InstallmentServiceContract;

class InstallmentService implements InstallmentServiceContract
{
    /**
     * Create the installment service with its dependencies.
     *
     * @param  InstallmentRepository  $installmentRepository  The card and installment writes.
     * @param  InstallmentQueryRepository  $installmentQueryRepository  The card and installment reads.
     * @param  CuotasSyncService  $cuotasSyncService  The cuotas lines sync.
     * @param  SpaceQueryRepository  $spaceQueryRepository  The space reads.
     */
    public function __construct(
        private readonly InstallmentRepository $installmentRepository,
        private readonly InstallmentQueryRepository $installmentQueryRepository,
        private readonly CuotasSyncService $cuotasSyncService,
        private readonly SpaceQueryRepository $spaceQueryRepository,
    ) {}

    /**
     * The cards of a space (primary when none is given) with their installments and totals (terminadas excluded from the
     * totals). porPeriodo splits the monthly cuota across the periods of the space that start in the current month;
     * porQuincena keeps the same amounts under its old name.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return array<int, array<string, mixed>> The card views; a missing space is a 404.
     */
    public function cardList(?int $spaceId = null): array
    {
        $space = $this->spaceQueryRepository->resolve($spaceId) ?? throw new NotFoundHttpException('No se encontró el espacio.');
        $parts = PeriodScheduleHelper::periodsStartingInMonth($space->Frequency, $space->CustomDays, now()->startOfMonth()->toDateString());
        $installments = $this->installmentQueryRepository->listInstallments($space->IdSpace)->groupBy('FkCard');

        return $this->installmentQueryRepository->listCards($space->IdSpace)->map(function (CardModel $card) use ($installments, $parts): array {
            $views = ($installments[$card->IdCard] ?? collect())->map(fn (InstallmentModel $i) => $this->toView($i));
            $live = $views->where('status', '!=', InstallmentStatusConstant::TERMINADA);
            $monthly = (int) $live->sum('cuota');
            $porPeriodo = array_map(fn (int $index) => MoneyHelper::splitAcross($monthly, $parts, $index), range(0, $parts - 1));

            return [
                'id' => $card->IdCard,
                'name' => $card->Name,
                'installments' => $views->values()->all(),
                'totals' => [
                    'cuotaMensual' => $monthly,
                    'saldoFaltante' => (int) $live->sum(fn (array $v) => $v['saldo']['value']),
                    'porQuincena' => $porPeriodo,
                    'porPeriodo' => $porPeriodo,
                ],
            ];
        })->all();
    }

    /**
     * Store a card in its space (primary when none is given).
     *
     * @param  CardStoreDto  $cardData  The card fields.
     * @return object The result of the store operation, or a 404 error when the space is not found.
     */
    public function storeCard(CardStoreDto $cardData): object
    {
        $space = $this->spaceQueryRepository->resolve($cardData->spaceId);

        return $space === null
            ? createValidationErrorResponse('No se encontró el espacio.', ['statusCode' => 404])
            : $this->installmentRepository->storeCard($cardData, $space->IdSpace);
    }

    /**
     * Store an installment and sync the cuotas lines in the same transaction.
     *
     * @param  InstallmentStoreDto  $installmentData  The installment fields.
     * @return object The result of the store operation.
     */
    public function storeInstallment(InstallmentStoreDto $installmentData): object
    {
        return $this->installmentRepository->transaction(function () use ($installmentData): object {
            if ($this->installmentQueryRepository->findCard($installmentData->cardId) === null) {
                return createValidationErrorResponse('No se encontró la tarjeta.', ['statusCode' => 404]);
            }

            $installment = $this->installmentRepository->insert($installmentData->toArray());
            $this->cuotasSyncService->syncWithinTransaction();

            return createValidationSuccessResponse('Cuota creada correctamente.', ['installment' => $this->toView($installment->fresh())]);
        }, 'No se pudo crear la cuota.');
    }

    /**
     * Update an installment and sync the cuotas lines in the same transaction.
     *
     * @param  InstallmentUpdateDto  $installmentData  The installment identifier and changed fields.
     * @return object The result of the update operation.
     */
    public function updateInstallment(InstallmentUpdateDto $installmentData): object
    {
        return $this->installmentRepository->transaction(function () use ($installmentData): object {
            $installment = $this->installmentQueryRepository->findInstallment($installmentData->installmentId, true);
            if ($installment === null) {
                return createValidationErrorResponse('No se encontró la cuota.', ['statusCode' => 404]);
            }
            if ($installmentData->cardId !== null && $this->installmentQueryRepository->findCard($installmentData->cardId) === null) {
                return createValidationErrorResponse('No se encontró la tarjeta.', ['statusCode' => 404]);
            }

            $this->installmentRepository->update($installment, $installmentData->toArray());
            $this->cuotasSyncService->syncWithinTransaction();

            return createValidationSuccessResponse('Cuota actualizada correctamente.', ['installment' => $this->toView($installment->fresh())]);
        }, 'No se pudo actualizar la cuota.');
    }

    /**
     * Delete an installment and sync the cuotas lines in the same transaction.
     *
     * @param  int  $installmentId  The installment identifier.
     * @return object The result of the delete operation.
     */
    public function deleteInstallment(int $installmentId): object
    {
        return $this->installmentRepository->transaction(function () use ($installmentId): object {
            $installment = $this->installmentQueryRepository->findInstallment($installmentId, true);
            if ($installment === null) {
                return createValidationErrorResponse('No se encontró la cuota.', ['statusCode' => 404]);
            }

            $this->installmentRepository->delete($installment);
            $this->cuotasSyncService->syncWithinTransaction();

            return createValidationSuccessResponse('Cuota eliminada correctamente.');
        }, 'No se pudo eliminar la cuota.');
    }

    /**
     * Toggle the cancel-soon flag of an installment.
     *
     * @param  int  $installmentId  The installment identifier.
     * @return object The result of the toggle operation.
     */
    public function toggleCancelSoon(int $installmentId): object
    {
        return $this->installmentRepository->transaction(function () use ($installmentId): object {
            $installment = $this->installmentQueryRepository->findInstallment($installmentId, true);
            if ($installment === null) {
                return createValidationErrorResponse('No se encontró la cuota.', ['statusCode' => 404]);
            }

            $this->installmentRepository->update($installment, ['CancelSoon' => ! $installment->CancelSoon]);

            return createValidationSuccessResponse('Cuota actualizada correctamente.', ['installment' => $this->toView($installment->fresh())]);
        }, 'No se pudo actualizar la cuota.');
    }

    /**
     * The JSON view: the calculator input plus merchant, item, cancelSoon, cuota, saldo and status.
     *
     * @param  InstallmentModel  $installment  The installment.
     * @return array<string, mixed> The view.
     */
    private function toView(InstallmentModel $installment): array
    {
        $input = $installment->toInput();

        return $input + [
            'merchant' => $installment->Merchant,
            'item' => $installment->Item,
            'cancelSoon' => $installment->CancelSoon,
            'cuota' => InstallmentCalculator::cuota($input),
            'saldo' => InstallmentCalculator::saldoFaltante($input),
            'status' => InstallmentCalculator::status($input),
        ];
    }
}
