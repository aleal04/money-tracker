<?php

namespace App\DTOs\Period;

use Illuminate\Http\Request;

class PeriodStoreDto
{
    /**
     * Create the period key to store.
     *
     * @param  int  $year  The year.
     * @param  int  $month  The month, 1 to 12.
     * @param  int  $half  The quincena, 1 or 2.
     */
    public function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly int $half,
    ) {}

    /**
     * Create a PeriodStoreDto from a request.
     *
     * @param  Request  $request  The HTTP request containing the period key.
     * @return PeriodStoreDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        return new self((int) $request->input('year'), (int) $request->input('month'), (int) $request->input('half'));
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array{year: int, month: int, half: int} The DTO data.
     */
    public function toArray(): array
    {
        return ['year' => $this->year, 'month' => $this->month, 'half' => $this->half];
    }
}
