<?php

namespace App\DTOs\Template;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Request;

class TemplateUpdateDto
{
    /**
     * Create the partial template update; only the fields sent are changed.
     *
     * @param  int  $templateId  The template identifier.
     * @param  string|null  $name  The new trimmed name, or null when not sent.
     * @param  string|null  $category  The new category, or null when not sent.
     * @param  int|null  $estimated  The new estimated amount in cents, or null when not sent.
     * @param  string|null  $frequency  The new frequency, or null when not sent.
     * @param  bool  $hasHalf  Whether half was sent.
     * @param  int|null  $half  The new quincena.
     * @param  bool|null  $active  The new active flag, or null when not sent.
     */
    public function __construct(
        public readonly int $templateId,
        public readonly ?string $name,
        public readonly ?string $category,
        public readonly ?int $estimated,
        public readonly ?string $frequency,
        public readonly bool $hasHalf,
        public readonly ?int $half,
        public readonly ?bool $active,
    ) {}

    /**
     * Create a TemplateUpdateDto from a validated request; the template comes from the route.
     *
     * @param  Request  $request  The HTTP request containing the changed fields.
     * @return TemplateUpdateDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            templateId: (int) $request->route('templateId'),
            name: $request->has('name') ? trim((string) $request->input('name')) : null,
            category: $request->has('category') ? (string) $request->input('category') : null,
            estimated: $request->has('estimated') ? (int) MoneyHelper::parseAmount((string) $request->input('estimated')) : null,
            frequency: $request->has('frequency') ? (string) $request->input('frequency') : null,
            hasHalf: $request->has('half'),
            half: $request->input('half') === null ? null : (int) $request->input('half'),
            active: $request->has('active') ? $request->boolean('active') : null,
        );
    }

    /**
     * Convert the DTO to the template columns that changed (money as decimal strings).
     *
     * @return array<string, mixed> The changed template columns.
     */
    public function toArray(): array
    {
        $changes = array_filter([
            'name' => $this->name,
            'category' => $this->category,
            'estimated' => $this->estimated === null ? null : MoneyHelper::fromCents($this->estimated),
            'frequency' => $this->frequency,
            'active' => $this->active,
        ], fn ($value) => $value !== null);

        if ($this->hasHalf) {
            $changes['half'] = $this->half;
        }

        return $changes;
    }
}
