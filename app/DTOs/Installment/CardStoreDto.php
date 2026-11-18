<?php

namespace App\DTOs\Installment;

use Illuminate\Http\Request;

class CardStoreDto
{
    /**
     * Create the card fields to store.
     *
     * @param  string  $name  The trimmed card name.
     */
    public function __construct(public readonly string $name) {}

    /**
     * Create a CardStoreDto from a validated request.
     *
     * @param  Request  $request  The HTTP request containing the card data.
     * @return CardStoreDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(trim((string) $request->input('name')));
    }

    /**
     * Convert the DTO to the card columns.
     *
     * @return array<string, mixed> The card columns.
     */
    public function toArray(): array
    {
        return ['Name' => $this->name];
    }
}
