<?php

namespace App\Http\Requests\Concerns;

use App\Constant\CategoryConstant;
use App\Constant\TemplateFrequencyConstant;
use Illuminate\Contracts\Validation\Validator;

/**
 * Field rules and the half check shared by the store and update template requests.
 */
trait ValidatesTemplateTrait
{
    use ValidatesAmountTrait;

    /**
     * The per-field rules.
     *
     * @param  bool  $partial  Whether every field is optional (update).
     * @return array<string, mixed> The rules.
     */
    protected function templateRules(bool $partial): array
    {
        $first = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'name' => [...$first, 'string', 'max:255'],
            'category' => [...$first, 'in:'.implode(',', CategoryConstant::ALL)],
            'estimated' => [...$first, $this->amountRule()],
            'frequency' => [...$first, 'in:'.implode(',', TemplateFrequencyConstant::ALL)],
            'half' => ['sometimes', 'nullable'],
            'active' => ['sometimes', 'boolean'],
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
            'name.*' => 'Valor requerido',
            'category.*' => 'Categoría inválida',
            'estimated.required' => 'Monto inválido',
            'frequency.*' => 'Frecuencia inválida',
            'active.*' => 'Valor inválido',
        ];
    }

    /**
     * Trim the name so a blank value fails as required.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /**
     * Half must be 1 or 2 when the merged frequency is mensual_en_quincena; otherwise it is ignored (stored null).
     *
     * @param  Validator  $validator  The validator.
     * @param  mixed  $frequency  The merged frequency.
     * @param  mixed  $half  The merged half.
     */
    protected function checkHalf(Validator $validator, mixed $frequency, mixed $half): void
    {
        $valid = in_array($half, [1, 2, '1', '2'], true);
        if ($frequency === TemplateFrequencyConstant::MENSUAL_EN_QUINCENA && ! $valid && ! $validator->errors()->has('half')) {
            $validator->errors()->add('half', 'Quincena requerida');
        }
    }
}
