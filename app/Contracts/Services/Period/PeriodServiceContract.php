<?php

namespace App\Contracts\Services\Period;

use App\Models\Space\SpaceModel;
use App\Models\Period\PeriodModel;
use App\DTOs\Period\PeriodStoreDto;

interface PeriodServiceContract
{
    /**
     * Create the period of a space (primary when none is given) in one transaction (idempotent on the start date).
     *
     * @param  PeriodStoreDto  $periodData  The space and dates.
     * @return object The response with the period view, or a 404 error.
     */
    public function storePeriod(PeriodStoreDto $periodData): object;

    /**
     * Create the period that follows the latest one of a space (primary when none is given).
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return object The response with the period view, or a 404/409 error.
     */
    public function storeNextPeriod(?int $spaceId = null): object;

    /**
     * Create the period its space's frequency proposes right after the given one; to run inside a transaction.
     *
     * @param  PeriodModel  $period  The period to continue from.
     * @return object The response with the period view.
     */
    public function createAfter(PeriodModel $period): object;

    /**
     * Create the period without opening a transaction, to run inside an outer one.
     *
     * @param  SpaceModel  $space  The space.
     * @param  string  $startDate  The start date 'Y-m-d'.
     * @param  string  $endDate  The end date 'Y-m-d'.
     * @return object The response with the period view.
     */
    public function createWithinTransaction(SpaceModel $space, string $startDate, string $endDate): object;

    /**
     * The id of the latest period of a space (primary when none is given), or null when there are none.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return int|null The latest period id.
     */
    public function latestPeriodId(?int $spaceId = null): ?int;

    /**
     * Whether a period exists.
     *
     * @param  int  $periodId  The period identifier.
     * @return bool True when found.
     */
    public function exists(int $periodId): bool;

    /**
     * A period with its neighbours, lines and summary.
     *
     * @param  int  $periodId  The period identifier.
     * @return array<string, mixed>|null The detail, or null when not found.
     */
    public function detail(int $periodId): ?array;
}
