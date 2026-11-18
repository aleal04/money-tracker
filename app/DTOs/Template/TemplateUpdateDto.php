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
     * @param  bool  $hasDayOfMonth  Whether dayOfMonth was sent.
     * @param  int|null  $dayOfMonth  The new day of the month.
     * @param  bool|null  $active  The new active flag, or null when not sent.
     * @param  bool  $hasTargetSpaceId  Whether targetSpaceId was sent (null clears it).
     * @param  int|null  $targetSpaceId  The new target space identifier.
     */
    public function __construct(
        public readonly int $templateId,
        public readonly ?string $name,
        public readonly ?string $category,
        public readonly ?int $estimated,
        public readonly ?string $frequency,
        public readonly bool $hasDayOfMonth,
        public readonly ?int $dayOfMonth,
        public readonly ?bool $active,
        public readonly bool $hasTargetSpaceId = false,
        public readonly ?int $targetSpaceId = null,
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
            hasDayOfMonth: $request->has('dayOfMonth'),
            dayOfMonth: $request->input('dayOfMonth') === null ? null : (int) $request->input('dayOfMonth'),
            active: $request->has('active') ? $request->boolean('active') : null,
            hasTargetSpaceId: $request->has('targetSpaceId'),
            targetSpaceId: $request->filled('targetSpaceId') ? (int) $request->input('targetSpaceId') : null,
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
            'Name' => $this->name,
            'Category' => $this->category,
            'Estimated' => $this->estimated === null ? null : MoneyHelper::fromCents($this->estimated),
            'Frequency' => $this->frequency,
            'Active' => $this->active,
        ], fn ($value) => $value !== null);

        if ($this->hasDayOfMonth) {
            $changes['DayOfMonth'] = $this->dayOfMonth;
        }

        if ($this->hasTargetSpaceId) {
            $changes['FkTargetSpace'] = $this->targetSpaceId;
        }

        return $changes;
    }
}
