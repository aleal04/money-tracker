<?php

namespace App\Repositories\Period;

use App\Models\Period\LineModel;
use App\DTOs\Period\LineStoreDto;
use App\DTOs\Period\LineUpdateDto;
use Illuminate\Support\Facades\DB;
use App\Constant\LineSourceConstant;
use App\Repositories\Concerns\RunsTransactionStepsTrait;

class LineRepository
{
    use RunsTransactionStepsTrait;

    /**
     * Create the line repository with its query dependencies.
     *
     * @param  LineQueryRepository  $lineQueryRepository  The line reads.
     * @param  PeriodQueryRepository  $periodQueryRepository  The period reads.
     */
    public function __construct(
        private readonly LineQueryRepository $lineQueryRepository,
        private readonly PeriodQueryRepository $periodQueryRepository,
    ) {}

    /**
     * Insert a line with the given columns; to run inside a transaction (period creation and cuotas sync).
     *
     * @param  array<string, mixed>  $attributes  The line columns.
     * @return LineModel The created line.
     */
    public function insert(array $attributes): LineModel
    {
        return LineModel::query()->create($attributes);
    }

    /**
     * Mark lines as carried into an arrastre line; to run inside a transaction.
     *
     * @param  list<int>  $lineIds  The carried line identifiers.
     * @param  int  $arrastreLineId  The arrastre line identifier.
     */
    public function markCarried(array $lineIds, int $arrastreLineId): void
    {
        LineModel::query()->whereIn('IdLine', $lineIds)->update(['FkCarriedToLine' => $arrastreLineId]);
    }

    /**
     * Un-carry every line carried into an arrastre line, then logically delete it; to run inside a transaction.
     *
     * @param  LineModel  $arrastre  The locked arrastre line.
     */
    public function removeArrastre(LineModel $arrastre): void
    {
        LineModel::query()->where('FkCarriedToLine', $arrastre->IdLine)->update(['FkCarriedToLine' => null]);
        $arrastre->logicalDelete();
    }

    /**
     * Store a manual line in a period, locking the period row.
     *
     * @param  LineStoreDto  $lineData  The line fields.
     * @return object The response with the line view, or a 404 error.
     */
    public function store(LineStoreDto $lineData): object
    {
        return $this->runTransactionSteps(DB::connection(), function () use ($lineData): object {
            if ($this->periodQueryRepository->find($lineData->periodId, true) === null) {
                return createValidationErrorResponse('No se encontró el periodo.', ['statusCode' => 404]);
            }

            $line = $this->insert($lineData->toArray() + ['Source' => LineSourceConstant::MANUAL]);

            return createValidationSuccessResponse('Línea creada correctamente.', ['line' => $line->fresh()->toView()]);
        }, 'No se pudo crear la línea.', ['statusCode' => 500]);
    }

    /**
     * Update the sent fields of a line; carried lines are immutable and a paid line of a closed period cannot be un-paid.
     *
     * @param  LineUpdateDto  $lineData  The line identifier and changed fields.
     * @return object The response with the line view, or a 404/409 error.
     */
    public function update(LineUpdateDto $lineData): object
    {
        return $this->runTransactionSteps(DB::connection(), function () use ($lineData): object {
            $line = $this->lineQueryRepository->find($lineData->lineId, true);
            $rejection = $this->rejectEdit($line, $lineData->paid === false);
            if ($rejection !== null) {
                return $rejection;
            }

            $changes = $lineData->toArray();
            if ($lineData->paid !== null && $lineData->paid !== (bool) $line->Paid) {
                $changes['PaidAt'] = $lineData->paid ? now() : null;
            }
            $line->update($changes);

            return createValidationSuccessResponse('Línea actualizada correctamente.', ['line' => $line->fresh()->toView()]);
        }, 'No se pudo actualizar la línea.', ['statusCode' => 500]);
    }

    /**
     * Flip the paid flag of a line, with the same rules as update.
     *
     * @param  int  $lineId  The line identifier.
     * @return object The response with the line view, or a 404/409 error.
     */
    public function togglePaid(int $lineId): object
    {
        return $this->runTransactionSteps(DB::connection(), function () use ($lineId): object {
            $line = $this->lineQueryRepository->find($lineId, true);
            $rejection = $this->rejectEdit($line, $line !== null && $line->Paid);
            if ($rejection !== null) {
                return $rejection;
            }

            $line->update(['Paid' => ! $line->Paid, 'PaidAt' => $line->Paid ? null : now()]);

            return createValidationSuccessResponse('Línea actualizada correctamente.', ['line' => $line->fresh()->toView()]);
        }, 'No se pudo actualizar la línea.', ['statusCode' => 500]);
    }

    /**
     * Logically delete a line; carried lines and the arrastre line cannot be deleted.
     *
     * @param  int  $lineId  The line identifier.
     * @return object The success response, or a 404/409 error.
     */
    public function destroy(int $lineId): object
    {
        return $this->runTransactionSteps(DB::connection(), function () use ($lineId): object {
            $line = $this->lineQueryRepository->find($lineId, true);
            $rejection = $this->rejectEdit($line, false);
            if ($rejection !== null) {
                return $rejection;
            }
            if ($line->Source === LineSourceConstant::CARRIED) {
                // Deleting would orphan the carried originals and resurrect debt in the closed quincena.
                return createValidationErrorResponse('Para quitar el arrastre, reabrí el periodo anterior', ['statusCode' => 409]);
            }

            $line->logicalDelete();

            return createValidationSuccessResponse('Línea eliminada correctamente.');
        }, 'No se pudo eliminar la línea.', ['statusCode' => 500]);
    }

    /**
     * Update the estimated amount of a line (cuotas sync); to run inside a transaction.
     *
     * @param  LineModel  $line  The line to update.
     * @param  string  $estimated  The new estimated amount as a decimal string.
     */
    public function updateEstimated(LineModel $line, string $estimated): void
    {
        $line->update(['Estimated' => $estimated]);
    }

    /**
     * Check the rules shared by every line edit.
     *
     * @param  LineModel|null  $line  The locked line.
     * @param  bool  $unpaying  Whether the edit would un-pay the line (only rejected when the line is paid in a closed period).
     * @return object|null The 404/409 error response, or null when the edit is allowed.
     */
    private function rejectEdit(?LineModel $line, bool $unpaying): ?object
    {
        if ($line === null) {
            return createValidationErrorResponse('No se encontró la línea.', ['statusCode' => 404]);
        }
        if ($line->FkCarriedToLine !== null) {
            return createValidationErrorResponse('Línea arrastrada', ['statusCode' => 409]);
        }
        // Un-paying in a closed quincena would leave an unpaid line that is never carried.
        if ($unpaying && $line->Paid && $this->periodQueryRepository->find($line->FkPeriod, true)?->Closed) {
            return createValidationErrorResponse('No se puede desmarcar un pago en un periodo cerrado', ['statusCode' => 409]);
        }

        return null;
    }
}
