<?php

namespace App\DTOs\Period;

use App\Helpers\MoneyHelper;
use Illuminate\Http\Request;

class LineStoreDto
{
    /**
     * Create the line fields to store; money is in cents.
     *
     * @param  int  $periodId  The period the line belongs to.
     * @param  string  $category  The category.
     * @param  string  $name  The trimmed name.
     * @param  int  $estimated  The estimated amount in cents.
     * @param  int|null  $actual  The actual amount in cents, or null.
     * @param  bool  $paid  Whether the line is paid.
     * @param  int|null  $targetSpaceId  The space the paid amount is sent to, or null.
     */
    public function __construct(
        public readonly int $periodId,
        public readonly string $category,
        public readonly string $name,
        public readonly int $estimated,
        public readonly ?int $actual,
        public readonly bool $paid,
        public readonly ?int $targetSpaceId = null,
    ) {}

    /**
     * Create a LineStoreDto from a validated request; the period comes from the route.
     *
     * @param  Request  $request  The HTTP request containing the line data.
     * @return LineStoreDto The created DTO.
     */
    public static function fromRequest(Request $request): self
    {
        $actual = $request->input('actual');

        return new self(
            periodId: (int) $request->route('periodId'),
            category: (string) $request->input('category'),
            name: trim((string) $request->input('name')),
            estimated: (int) MoneyHelper::parseAmount((string) $request->input('estimated')),
            actual: $actual === null ? null : MoneyHelper::parseAmount((string) $actual),
            paid: $request->boolean('paid'),
            targetSpaceId: $request->filled('targetSpaceId') ? (int) $request->input('targetSpaceId') : null,
        );
    }

    /**
     * Convert the DTO to the line columns (money as decimal strings, paid_at set when paid).
     *
     * @return array<string, mixed> The line columns.
     */
    public function toArray(): array
    {
        return [
            'FkPeriod' => $this->periodId,
            'Category' => $this->category,
            'Name' => $this->name,
            'Estimated' => MoneyHelper::fromCents($this->estimated),
            'Actual' => $this->actual === null ? null : MoneyHelper::fromCents($this->actual),
            'Paid' => $this->paid,
            'PaidAt' => $this->paid ? now() : null,
            'FkTargetSpace' => $this->targetSpaceId,
        ];
    }
}
