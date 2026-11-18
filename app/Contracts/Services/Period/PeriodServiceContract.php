<?php

namespace App\Contracts\Services\Period;

use App\DTOs\Period\PeriodStoreDto;

interface PeriodServiceContract
{
    /**
     * Create the period of the given key in one transaction (idempotent).
     *
     * @param  PeriodStoreDto  $periodData  The period key.
     * @return object The response with the period view.
     */
    public function storePeriod(PeriodStoreDto $periodData): object;

    /**
     * Create the period that follows the latest one.
     *
     * @return object The response with the period view, or a 409 error when there are no periods.
     */
    public function storeNextPeriod(): object;

    /**
     * Create the period without opening a transaction, to run inside an outer one.
     *
     * @param  array{year:int,month:int,half:int}  $key  The period key.
     * @return object The response with the period view.
     */
    public function createWithinTransaction(array $key): object;

    /**
     * The id of the latest period, or null when there are none.
     *
     * @return int|null The latest period id.
     */
    public function latestPeriodId(): ?int;

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
