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
     * @param  int|null  $half  The quincena (1 or 2), only for mensual_en_quincena.
     * @param  bool  $active  Whether the template is expanded into new periods.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $category,
        public readonly int $estimated,
        public readonly string $frequency,
        public readonly ?int $half,
        public readonly bool $active,
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
            half: $frequency === TemplateFrequencyConstant::MENSUAL_EN_QUINCENA ? (int) $request->input('half') : null,
            active: $request->boolean('active', true),
        );
    }

    /**
     * Convert the DTO to the template columns.
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
            'Half' => $this->half,
            'Active' => $this->active,
        ];
    }
}
