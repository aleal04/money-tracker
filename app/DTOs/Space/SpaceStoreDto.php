<?php

namespace App\DTOs\Space;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Request;
use App\Constant\SpaceFrequencyConstant;

class SpaceStoreDto
{
    /**
     * Create the space fields to store; money is in cents.
     *
     * @param  string  $name  The trimmed name.
     * @param  string  $frequency  The period frequency.
     * @param  int|null  $customDays  The days per period, only for custom.
     * @param  int  $initialBalance  The initial balance in cents.
     * @param  bool  $isPrimary  Whether the space becomes the primary one.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $frequency,
        public readonly ?int $customDays,
        public readonly int $initialBalance,
        public readonly bool $isPrimary,
    ) {}

    /**
     * Create a SpaceStoreDto from a validated request.
     *
     * @param  Request  $request  The HTTP request containing the space data.
     * @return SpaceStoreDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        $frequency = (string) $request->input('frequency');

        return new self(
            name: trim((string) $request->input('name')),
            frequency: $frequency,
            customDays: $frequency === SpaceFrequencyConstant::CUSTOM ? (int) $request->input('customDays') : null,
            initialBalance: $request->filled('initialBalance') ? (int) MoneyHelper::parseAmount((string) $request->input('initialBalance')) : 0,
            isPrimary: $request->boolean('isPrimary'),
        );
    }

    /**
     * Convert the DTO to the space columns (IsPrimary is decided by the service).
     *
     * @return array<string, mixed> The space columns.
     */
    public function toArray(): array
    {
        return [
            'Name' => $this->name,
            'Frequency' => $this->frequency,
            'CustomDays' => $this->customDays,
            'InitialBalance' => MoneyHelper::fromCents($this->initialBalance),
        ];
    }
}
