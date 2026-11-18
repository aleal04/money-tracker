<?php

namespace App\DTOs\Installment;

use Illuminate\Http\Request;

class CardStoreDto
{
    /**
     * Create the card fields to store.
     *
     * @param  string  $name  The trimmed card name.
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     */
    public function __construct(public readonly string $name, public readonly ?int $spaceId = null) {}

    /**
     * Create a CardStoreDto from a validated request.
     *
     * @param  Request  $request  The HTTP request containing the card data.
     * @return CardStoreDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(trim((string) $request->input('name')), $request->filled('spaceId') ? (int) $request->input('spaceId') : null);
    }

    /**
     * Convert the DTO to the card columns (the service resolves FkSpace).
     *
     * @return array<string, mixed> The card columns.
     */
    public function toArray(): array
    {
        return ['Name' => $this->name];
    }
}
