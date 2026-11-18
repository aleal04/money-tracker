<?php

namespace App\Http\Requests\Period;

use App\Constant\CategoryConstant;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\ValidatesAmountTrait;

class UpdateLineFormRequest extends FormRequest
{
    use ValidatesAmountTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request; every field is optional (partial update).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'required', 'in:'.implode(',', CategoryConstant::ALL)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'estimated' => ['sometimes', 'required', $this->amountRule()],
            'actual' => ['sometimes', 'nullable', $this->amountRule()],
            'paid' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * Get the validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.*' => 'Categoría inválida',
            'name.*' => 'Nombre requerido',
            'estimated.required' => 'Monto inválido',
            'paid.*' => 'Valor inválido',
        ];
    }

    /**
     * Trim the name so a blank name fails as required.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }
}
