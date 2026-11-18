<?php

namespace App\Repositories\Period;

use App\Models\Period\LineModel;
use App\Constant\GeneralConstant;
use App\Constant\CategoryConstant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * @param  bool  $lock  Whether to lock the rows (FOR UPDATE).
     * @return Collection<int, LineModel> The lines.
     */
    public function listByPeriod(int $periodId, bool $lock = false): Collection
    {
        $query = LineModel::query()->where('FkPeriod', $periodId)->orderBy('Sort')->orderBy('IdLine');

        return ($lock ? $query->lockForUpdate() : $query)->get();
    }

    /**
     * The original lines carried into the given (arrastre) line.
     *
     * @param  int  $lineId  The arrastre line identifier.
     * @return Collection<int, LineModel> The carried lines.
     */
    public function listCarriedTo(int $lineId): Collection
    {
        return LineModel::query()->where('FkCarriedToLine', $lineId)->orderBy('Sort')->orderBy('IdLine')->get();
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
            ->where(['FkPeriod' => $periodId, 'FkCard' => $cardId, 'Source' => LineSourceConstant::INSTALLMENTS])
            ->first();
    }

    /**
     * Ingreso and egreso in cents (actual, else estimated; carried lines left out) of every live period of a space that
     * starts on or before a date, in a single grouped query.
     *
     * @param  int  $spaceId  The space identifier.
     * @param  string  $startDate  The last start date to include 'Y-m-d'.
     * @return Collection<int, object{ingreso:int,egreso:int}> The totals keyed by period id (periods without lines are absent).
     */
    public function totalsBySpaceUpTo(int $spaceId, string $startDate): Collection
    {
        $value = 'ROUND(COALESCE(`Line`.`Actual`, `Line`.`Estimated`) * 100)';

        return DB::table('Line')
            ->join('Period', 'Period.IdPeriod', '=', 'Line.FkPeriod')
            ->where('Period.FkSpace', $spaceId)->where('Period.IsDeleted', GeneralConstant::NOT_DELETED)->where('Period.StartDate', '<=', $startDate)
            ->where('Line.IsDeleted', GeneralConstant::NOT_DELETED)->whereNull('Line.FkCarriedToLine')
            ->groupBy('Line.FkPeriod')
            ->selectRaw('`Line`.`FkPeriod` AS `FkPeriod`, SUM(CASE WHEN `Line`.`Category` = ? THEN '.$value.' ELSE 0 END) AS `Ingreso`, SUM(CASE WHEN `Line`.`Category` <> ? THEN '.$value.' ELSE 0 END) AS `Egreso`', [CategoryConstant::INGRESO, CategoryConstant::INGRESO])
            ->get()
            ->keyBy('FkPeriod')
            ->map(fn (object $row) => (object) ['ingreso' => (int) round((float) $row->Ingreso), 'egreso' => (int) round((float) $row->Egreso)]);
    }
}
