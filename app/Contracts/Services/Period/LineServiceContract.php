<?php

namespace App\Contracts\Services\Period;

use App\DTOs\Period\LineStoreDto;
use App\DTOs\Period\LineUpdateDto;

interface LineServiceContract
{
    /**
     * Store a manual line in a period.
     *
     * @param  LineStoreDto  $lineData  The line fields.
     * @return object The result of the store operation.
     */
    public function storeLine(LineStoreDto $lineData): object;

    /**
     * Update a line.
     *
     * @param  LineUpdateDto  $lineData  The line identifier and changed fields.
     * @return object The result of the update operation.
     */
    public function updateLine(LineUpdateDto $lineData): object;

    /**
     * Toggle the paid flag of a line.
     *
     * @param  int  $lineId  The line identifier.
     * @return object The result of the toggle operation.
     */
    public function togglePaid(int $lineId): object;

    /**
     * Delete a line.
     *
     * @param  int  $lineId  The line identifier.
     * @return object The result of the delete operation.
     */
    public function deleteLine(int $lineId): object;

    /**
     * The original lines carried into an arrastre line, as views.
     *
     * @param  int  $lineId  The arrastre line identifier.
     * @return array<int, array<string, mixed>> The line views.
     */
    public function arrastreDetail(int $lineId): array;
}
