<?php

namespace App\Http\Requests\Concerns;

use Closure;
use App\Helpers\MoneyHelper;

/**
 * Validation of es-CR money inputs with MoneyHelper::parseAmount.
 */
trait ValidatesAmountTrait
{
    /**
     * A rule that fails with "Monto inválido" when the value is not a parseable amount.
     *
     * @return Closure(string, mixed, Closure): void The closure rule.
     */
    protected function amountRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! (is_string($value) || is_int($value) || is_float($value)) || MoneyHelper::parseAmount((string) $value) === null) {
                $fail('Monto inválido');
            }
        };
    }
}
