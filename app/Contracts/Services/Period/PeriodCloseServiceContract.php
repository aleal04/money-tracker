<?php

namespace App\Contracts\Services\Period;

interface PeriodCloseServiceContract
{
    /**
     * The unpaid egreso lines a close would carry, with their total in cents.
     *
     * @param  int  $periodId  The period identifier.
     * @return array{lines: array<int, array<string, mixed>>, total: int}|null The preview, or null when the period is not found.
     */
    public function preview(int $periodId): ?array;

    /**
     * Close the period in one transaction, carrying its unpaid egresos into one arrastre line of the next period.
     *
     * @param  int  $periodId  The period identifier.
     * @return object The response with arrastreLineId (null when nothing was carried), or a 404/409 error.
     */
    public function close(int $periodId): object;

    /**
     * Reopen the period in one transaction, deleting its unpaid arrastre line.
     *
     * @param  int  $periodId  The period identifier.
     * @return object The success response, or a 404/409 error.
     */
    public function reopen(int $periodId): object;
}
