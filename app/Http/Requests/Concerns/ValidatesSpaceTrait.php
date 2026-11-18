<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use App\Constant\GeneralConstant;
use App\Constant\SpaceFrequencyConstant;
use Illuminate\Contracts\Validation\Validator;

/**
 * Field rules and the custom-days check shared by the store and update space requests.
 */
trait ValidatesSpaceTrait
{
    use ValidatesAmountTrait;

    /**
     * The per-field rules; the name is unique among the live spaces.
     *
     * @param  bool  $partial  Whether every field is optional (update).
     * @param  int|null  $ignoreId  The space being updated, left out of the uniqueness check.
     * @return array<string, mixed> The rules.
     */
    protected function spaceRules(bool $partial, ?int $ignoreId = null): array
    {
        $first = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'name' => [...$first, 'string', 'max:255', Rule::unique('Space', 'Name')->where(fn ($query) => $query->where('IsDeleted', GeneralConstant::NOT_DELETED))->ignore($ignoreId, 'IdSpace')],
            'frequency' => [...$first, 'in:'.implode(',', SpaceFrequencyConstant::ALL)],
            'customDays' => ['sometimes', 'nullable'],
            'initialBalance' => $partial ? ['sometimes', $this->amountRule()] : ['sometimes', 'nullable', $this->amountRule()],
            'isPrimary' => ['sometimes', 'boolean'],
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
            'name.unique' => 'Ya existe un espacio con ese nombre',
            'name.*' => 'Valor requerido',
            'frequency.*' => 'Frecuencia inválida',
            'isPrimary.*' => 'Valor inválido',
        ];
    }

    /**
     * Trim the name so a blank value fails as required and duplicates are detected.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    /**
     * customDays must be an integer 1 to 366 when the merged frequency is custom; otherwise it is ignored (stored null).
     *
     * @param  Validator  $validator  The validator.
     * @param  mixed  $frequency  The merged frequency.
     * @param  mixed  $customDays  The merged days per period.
     */
    protected function checkCustomDays(Validator $validator, mixed $frequency, mixed $customDays): void
    {
        $days = filter_var($customDays, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 366]]);
        if ($frequency === SpaceFrequencyConstant::CUSTOM && $days === false) {
            $validator->errors()->add('customDays', 'Días inválidos (1 a 366)');
        }
    }
}
