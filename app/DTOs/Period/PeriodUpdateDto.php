<?php

namespace App\DTOs\Period;

use Illuminate\Http\Request;

class PeriodUpdateDto
{
    /**
     * Create the new dates of a period.
     *
     * @param  string  $startDate  The start date 'Y-m-d'.
     * @param  string  $endDate  The end date 'Y-m-d'.
     */
    public function __construct(
        public readonly string $startDate,
        public readonly string $endDate,
    ) {}

    /**
     * Create a PeriodUpdateDto from a validated request.
     *
     * @param  Request  $request  The HTTP request containing the dates.
     * @return PeriodUpdateDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        return new self((string) $request->input('startDate'), (string) $request->input('endDate'));
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array{startDate: string, endDate: string} The DTO data.
     */
    public function toArray(): array
    {
        return ['startDate' => $this->startDate, 'endDate' => $this->endDate];
    }
}
