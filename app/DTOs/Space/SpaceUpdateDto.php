<?php

namespace App\DTOs\Space;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Request;

class SpaceUpdateDto
{
    /**
     * Create the partial space update; only the fields sent are changed.
     *
     * @param  int  $spaceId  The space identifier.
     * @param  string|null  $name  The new trimmed name, or null when not sent.
     * @param  string|null  $frequency  The new frequency, or null when not sent.
     * @param  bool  $hasCustomDays  Whether customDays was sent.
     * @param  int|null  $customDays  The new days per period.
     * @param  int|null  $initialBalance  The new initial balance in cents, or null when not sent.
     */
    public function __construct(
        public readonly int $spaceId,
        public readonly ?string $name,
        public readonly ?string $frequency,
        public readonly bool $hasCustomDays,
        public readonly ?int $customDays,
        public readonly ?int $initialBalance,
    ) {}

    /**
     * Create a SpaceUpdateDto from a validated request; the space comes from the route.
     *
     * @param  Request  $request  The HTTP request containing the changed fields.
     * @return SpaceUpdateDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            spaceId: (int) $request->route('spaceId'),
            name: $request->has('name') ? trim((string) $request->input('name')) : null,
            frequency: $request->has('frequency') ? (string) $request->input('frequency') : null,
            hasCustomDays: $request->has('customDays'),
            customDays: $request->input('customDays') === null ? null : (int) $request->input('customDays'),
            initialBalance: $request->has('initialBalance') ? (int) MoneyHelper::parseAmount((string) $request->input('initialBalance')) : null,
        );
    }

    /**
     * Convert the DTO to the space columns that changed (money as decimal strings).
     *
     * @return array<string, mixed> The changed space columns.
     */
    public function toArray(): array
    {
        $changes = array_filter([
            'Name' => $this->name,
            'Frequency' => $this->frequency,
            'InitialBalance' => $this->initialBalance === null ? null : MoneyHelper::fromCents($this->initialBalance),
        ], fn ($value) => $value !== null);

        if ($this->hasCustomDays) {
            $changes['CustomDays'] = $this->customDays;
        }

        return $changes;
    }
}
