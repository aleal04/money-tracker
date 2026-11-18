<?php

namespace App\Contracts\Services\Period;

interface CuotasSyncServiceContract
{
    /**
     * Sync the cuotas lines in their own transaction.
     *
     * @param  int|null  $periodId  The period to sync, or null for every open period since the latest I quincena month.
     * @return object The result of the sync.
     */
    public function sync(?int $periodId = null): object;

    /**
     * Sync the cuotas lines without opening a transaction, to join an outer one.
     *
     * @param  int|null  $periodId  The period to sync, or null for the all-open scope.
     */
    public function syncWithinTransaction(?int $periodId = null): void;
}
