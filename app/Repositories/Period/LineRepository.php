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
        LineModel::query()->whereIn('id', $lineIds)->update(['carried_to_line_id' => $arrastreLineId]);
    }

    /**
     * Un-carry every line carried into an arrastre line, then delete it; to run inside a transaction.
     *
     * @param  LineModel  $arrastre  The locked arrastre line.
     */
    public function removeArrastre(LineModel $arrastre): void
    {
        LineModel::query()->where('carried_to_line_id', $arrastre->id)->update(['carried_to_line_id' => null]);
        $arrastre->delete();
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
                return createValidationErrorResponse('No se encontró la quincena.', ['statusCode' => 404]);
            }

            $line = $this->insert($lineData->toArray() + ['source' => LineSourceConstant::MANUAL]);

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
            if ($lineData->paid !== null && $lineData->paid !== (bool) $line->paid) {
                $changes['paid_at'] = $lineData->paid ? now() : null;
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
            $rejection = $this->rejectEdit($line, $line !== null && $line->paid);
            if ($rejection !== null) {
                return $rejection;
            }

            $line->update(['paid' => ! $line->paid, 'paid_at' => $line->paid ? null : now()]);

            return createValidationSuccessResponse('Línea actualizada correctamente.', ['line' => $line->fresh()->toView()]);
        }, 'No se pudo actualizar la línea.', ['statusCode' => 500]);
    }

    /**
     * Delete a line; carried lines and the arrastre line cannot be deleted.
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
            if ($line->source === LineSourceConstant::CARRIED) {
                // Deleting would un-carry the originals (FK SET NULL) and resurrect debt in the closed quincena.
                return createValidationErrorResponse('Para quitar el arrastre, reabrí la quincena anterior', ['statusCode' => 409]);
            }

            $line->delete();

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
        $line->update(['estimated' => $estimated]);
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
        if ($line->carried_to_line_id !== null) {
            return createValidationErrorResponse('Línea arrastrada', ['statusCode' => 409]);
        }
        // Un-paying in a closed quincena would leave an unpaid line that is never carried.
        if ($unpaying && $line->paid && $this->periodQueryRepository->find($line->period_id, true)?->closed) {
            return createValidationErrorResponse('No se puede desmarcar un pago en una quincena cerrada', ['statusCode' => 409]);
        }

        return null;
    }
}
