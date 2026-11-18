<?php

namespace App\Repositories\Period;

use App\Models\Period\LineModel;
use Illuminate\Support\Collection;
use App\Constant\LineSourceConstant;
use App\Repositories\BaseQueryRepository;

class LineQueryRepository extends BaseQueryRepository
{
    /**
     * Find a line by identifier, optionally locking its row for the current transaction.
     *
     * @param  int  $lineId  The line identifier.
     * @param  bool  $lock  Whether to lock the row (FOR UPDATE).
     * @return LineModel|null The line, or null when not found.
     */
    public function find(int $lineId, bool $lock = false): ?LineModel
    {
        $query = LineModel::query();

        return ($lock ? $query->lockForUpdate() : $query)->find($lineId);
    }

    /**
     * The lines of a period in display order.
     *
     * @param  int  $periodId  The period identifier.
     * @return Collection<int, LineModel> The lines.
     */
    public function listByPeriod(int $periodId): Collection
    {
        return LineModel::query()->where('period_id', $periodId)->orderBy('sort')->orderBy('id')->get();
    }

    /**
     * The original lines carried into the given (arrastre) line.
     *
     * @param  int  $lineId  The arrastre line identifier.
     * @return Collection<int, LineModel> The carried lines.
     */
    public function listCarriedTo(int $lineId): Collection
    {
        return LineModel::query()->where('carried_to_line_id', $lineId)->orderBy('sort')->orderBy('id')->get();
    }

    /**
     * The installments line of a card in a period.
     *
     * @param  int  $periodId  The period identifier.
     * @param  int  $cardId  The card identifier.
     * @return LineModel|null The line, or null when there is none.
     */
    public function findInstallmentsLine(int $periodId, int $cardId): ?LineModel
    {
        return LineModel::query()
            ->where(['period_id' => $periodId, 'card_id' => $cardId, 'source' => LineSourceConstant::INSTALLMENTS])
            ->first();
    }
}
