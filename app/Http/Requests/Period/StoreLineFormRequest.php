<?php

namespace App\Http\Requests\Period;

use App\Constant\CategoryConstant;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Concerns\ValidatesAmountTrait;

class StoreLineFormRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'in:'.implode(',', CategoryConstant::ALL)],
            'name' => ['required', 'string', 'max:255'],
            'estimated' => ['required', $this->amountRule()],
            'actual' => ['nullable', $this->amountRule()],
            'targetSpaceId' => ['nullable', 'integer', 'exists:Space,IdSpace,IsDeleted,0'],
            'paid' => ['nullable', 'boolean'],
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
            'targetSpaceId.*' => 'Espacio inválido',
            'estimated.required' => 'Monto inválido',
            'paid.boolean' => 'Valor inválido',
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
