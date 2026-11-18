<?php

namespace App\Http\Requests\Concerns;

use App\Helpers\MoneyHelper;
use App\Constant\InstallmentTypeConstant;
use Illuminate\Contracts\Validation\Validator;

/**
 * Field rules and cross-field checks shared by the store and update installment requests.
 */
trait ValidatesInstallmentTrait
{
    use ValidatesAmountTrait;

    /**
     * The per-field rules.
     *
     * @param  bool  $partial  Whether every field is optional (update).
     * @return array<string, mixed> The rules.
     */
    protected function installmentRules(bool $partial): array
    {
        $first = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'cardId' => [...$first, 'integer'],
            'merchant' => [...$first, 'string', 'max:255'],
            'item' => [...$first, 'string', 'max:255'],
            'totalInstallments' => [...$first, 'integer', 'min:1', 'max:65535'],
            'paidInstallments' => [...$first, 'integer', 'min:0'],
            'type' => [...$first, 'in:'.implode(',', InstallmentTypeConstant::ALL)],
            'initialBalance' => [...$first, $this->amountRule()],
            'installmentAmount' => ['sometimes', 'nullable', $this->amountRule()],
            'balanceOverride' => ['sometimes', 'nullable', $this->amountRule()],
        ];
    }

    /**
     * The validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cardId.*' => 'Tarjeta inválida',
            'merchant.*' => 'Valor requerido',
            'item.*' => 'Valor requerido',
            'totalInstallments.*' => 'Cuotas totales inválidas',
            'paidInstallments.*' => 'Cuotas pagadas inválidas',
            'type.*' => 'Tipo inválido',
            'initialBalance.required' => 'Monto inválido',
        ];
    }

    /**
     * Trim the text fields so a blank value fails as required.
     */
    protected function prepareForValidation(): void
    {
        foreach (['merchant', 'item'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => trim($this->input($key))]);
            }
        }
    }

    /**
     * Cross-field checks on the full (merged) installment; a field that already failed keeps its own error.
     *
     * @param  Validator  $validator  The validator.
     * @param  array<string, mixed>  $merged  The installment values after applying the request.
     */
    protected function checkInstallment(Validator $validator, array $merged): void
    {
        $fail = fn (string $field, string $message) => $validator->errors()->has($field) || $validator->errors()->add($field, $message);

        $total = $merged['totalInstallments'] ?? null;
        $paid = $merged['paidInstallments'] ?? null;
        if (is_numeric($total) && is_numeric($paid) && (int) $paid > (int) $total) {
            $fail('paidInstallments', 'Cuotas pagadas inválidas');
        }
        if (($merged['type'] ?? null) === InstallmentTypeConstant::MINICUOTAS && ($merged['installmentAmount'] ?? null) === null) {
            $fail('installmentAmount', 'Monto de cuota requerido');
        }
        $balance = $merged['initialBalance'] ?? null;
        if (is_scalar($balance) && MoneyHelper::parseAmount((string) $balance) === 0) {
            $fail('initialBalance', 'Monto inválido');
        }
    }
}
