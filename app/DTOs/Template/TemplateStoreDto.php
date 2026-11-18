<?php

namespace App\DTOs\Template;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Request;
use App\Constant\TemplateFrequencyConstant;

class TemplateStoreDto
{
    /**
     * Create the template fields to store; money is in cents.
     *
     * @param  string  $name  The trimmed name.
     * @param  string  $category  The category.
     * @param  int  $estimated  The estimated amount in cents.
     * @param  string  $frequency  The frequency.
     * @param  int|null  $dayOfMonth  The day of the month (1 to 31), only for mensual_dia.
     * @param  bool  $active  Whether the template is expanded into new periods.
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $category,
        public readonly int $estimated,
        public readonly string $frequency,
        public readonly ?int $dayOfMonth,
        public readonly bool $active,
        public readonly ?int $spaceId,
    ) {}

    /**
     * Create a TemplateStoreDto from a validated request.
     *
     * @param  Request  $request  The HTTP request containing the template data.
     * @return TemplateStoreDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        $frequency = (string) $request->input('frequency');

        return new self(
            name: trim((string) $request->input('name')),
            category: (string) $request->input('category'),
            estimated: (int) MoneyHelper::parseAmount((string) $request->input('estimated')),
            frequency: $frequency,
            dayOfMonth: $frequency === TemplateFrequencyConstant::MENSUAL_DIA ? (int) $request->input('dayOfMonth') : null,
            active: $request->boolean('active', true),
            spaceId: $request->filled('spaceId') ? (int) $request->input('spaceId') : null,
        );
    }

    /**
     * Convert the DTO to the template columns (the service adds FkSpace).
     *
     * @return array<string, mixed> The template columns.
     */
    public function toArray(): array
    {
        return [
            'Name' => $this->name,
            'Category' => $this->category,
            'Estimated' => MoneyHelper::fromCents($this->estimated),
            'Frequency' => $this->frequency,
            'DayOfMonth' => $this->dayOfMonth,
            'Active' => $this->active,
        ];
    }
}
