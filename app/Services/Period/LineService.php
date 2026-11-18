<?php

namespace App\Services\Period;

use App\Models\Period\LineModel;
use App\DTOs\Period\LineStoreDto;
use App\DTOs\Period\LineUpdateDto;
use App\Repositories\Period\LineRepository;
use App\Repositories\Period\LineQueryRepository;
use App\Contracts\Services\Period\LineServiceContract;

class LineService implements LineServiceContract
{
    /**
     * Create the line service with its dependencies.
     *
     * @param  LineRepository  $lineRepository  The line writes.
     * @param  LineQueryRepository  $lineQueryRepository  The line reads.
     */
    public function __construct(
        private readonly LineRepository $lineRepository,
        private readonly LineQueryRepository $lineQueryRepository,
    ) {}

    /**
     * Store a manual line in a period.
     *
     * @param  LineStoreDto  $lineData  The line fields.
     * @return object The result of the store operation.
     */
    public function storeLine(LineStoreDto $lineData): object
    {
        return $this->lineRepository->store($lineData);
    }

    /**
     * Update a line.
     *
     * @param  LineUpdateDto  $lineData  The line identifier and changed fields.
     * @return object The result of the update operation.
     */
    public function updateLine(LineUpdateDto $lineData): object
    {
        return $this->lineRepository->update($lineData);
    }

    /**
     * Toggle the paid flag of a line.
     *
     * @param  int  $lineId  The line identifier.
     * @return object The result of the toggle operation.
     */
    public function togglePaid(int $lineId): object
    {
        return $this->lineRepository->togglePaid($lineId);
    }

    /**
     * Delete a line.
     *
     * @param  int  $lineId  The line identifier.
     * @return object The result of the delete operation.
     */
    public function deleteLine(int $lineId): object
    {
        return $this->lineRepository->destroy($lineId);
    }

    /**
     * The original lines carried into an arrastre line, as views.
     *
     * @param  int  $lineId  The arrastre line identifier.
     * @return array<int, array<string, mixed>> The line views.
     */
    public function arrastreDetail(int $lineId): array
    {
        return $this->lineQueryRepository->listCarriedTo($lineId)->map(fn (LineModel $line) => $line->toView())->all();
    }
}
