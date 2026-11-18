<?php

namespace App\Http\Requests\Concerns;

use Closure;
use App\Helpers\MoneyHelper;

/**
 * Validation of es-CR money inputs with MoneyHelper::parseAmount.
 */
trait ValidatesAmountTrait
{
    /** The largest accepted amount, in cents (999999999999.99). */
    private const MAX_AMOUNT_CENTS = 99999999999999;

    /**
     * A rule that fails with "Monto inválido" when the value is not a parseable amount or exceeds 999999999999.99.
     *
     * @return Closure(string, mixed, Closure): void The closure rule.
     */
    protected function amountRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $cents = is_string($value) || is_int($value) || is_float($value) ? MoneyHelper::parseAmount((string) $value) : null;
            if ($cents === null || $cents > self::MAX_AMOUNT_CENTS) {
                $fail('Monto inválido');
            }
        };
    }
}
