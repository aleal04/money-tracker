<?php

namespace App\DTOs\Period;

use Illuminate\Http\Request;

class PeriodStoreDto
{
    /**
     * Create the period to store.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @param  string  $startDate  The start date 'Y-m-d'.
     * @param  string  $endDate  The end date 'Y-m-d'.
     */
    public function __construct(
        public readonly ?int $spaceId,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {}

    /**
     * Create a PeriodStoreDto from a validated request.
     *
     * @param  Request  $request  The HTTP request containing the space and dates.
     * @return PeriodStoreDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->filled('spaceId') ? (int) $request->input('spaceId') : null,
            (string) $request->input('startDate'),
            (string) $request->input('endDate'),
        );
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array{spaceId: ?int, startDate: string, endDate: string} The DTO data.
     */
    public function toArray(): array
    {
        return ['spaceId' => $this->spaceId, 'startDate' => $this->startDate, 'endDate' => $this->endDate];
    }
}
