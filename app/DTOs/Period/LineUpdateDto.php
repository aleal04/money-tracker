<?php

namespace App\DTOs\Period;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Request;

class LineUpdateDto
{
    /**
     * Create the partial line update; only the fields sent are changed.
     *
     * @param  int  $lineId  The line identifier.
     * @param  string|null  $category  The new category, or null when not sent.
     * @param  string|null  $name  The new trimmed name, or null when not sent.
     * @param  int|null  $estimated  The new estimated amount in cents, or null when not sent.
     * @param  bool  $hasActual  Whether actual was sent (it may be sent as null to clear it).
     * @param  int|null  $actual  The new actual amount in cents.
     * @param  bool|null  $paid  The new paid flag, or null when not sent.
     */
    public function __construct(
        public readonly int $lineId,
        public readonly ?string $category,
        public readonly ?string $name,
        public readonly ?int $estimated,
        public readonly bool $hasActual,
        public readonly ?int $actual,
        public readonly ?bool $paid,
    ) {}

    /**
     * Create a LineUpdateDto from a validated request; the line comes from the route.
     *
     * @param  Request  $request  The HTTP request containing the changed fields.
     * @return LineUpdateDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        $actual = $request->input('actual');

        return new self(
            lineId: (int) $request->route('lineId'),
            category: $request->has('category') ? (string) $request->input('category') : null,
            name: $request->has('name') ? trim((string) $request->input('name')) : null,
            estimated: $request->has('estimated') ? (int) MoneyHelper::parseAmount((string) $request->input('estimated')) : null,
            hasActual: $request->has('actual'),
            actual: $actual === null ? null : MoneyHelper::parseAmount((string) $actual),
            paid: $request->has('paid') ? $request->boolean('paid') : null,
        );
    }

    /**
     * Convert the DTO to the line columns that changed (money as decimal strings).
     *
     * @return array<string, mixed> The changed line columns.
     */
    public function toArray(): array
    {
        $changes = array_filter([
            'Category' => $this->category,
            'Name' => $this->name,
            'Estimated' => $this->estimated === null ? null : MoneyHelper::fromCents($this->estimated),
            'Paid' => $this->paid,
        ], fn ($value) => $value !== null);

        if ($this->hasActual) {
            $changes['Actual'] = $this->actual === null ? null : MoneyHelper::fromCents($this->actual);
        }

        return $changes;
    }
}
