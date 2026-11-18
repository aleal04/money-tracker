<?php

namespace App\Http\Requests\Installment;

use Illuminate\Validation\Rule;
use App\Constant\GeneralConstant;
use Illuminate\Foundation\Http\FormRequest;

class StoreCardFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request; the name is unique among the cards that are not deleted.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('Card', 'Name')->where(fn ($query) => $query->where('IsDeleted', GeneralConstant::NOT_DELETED))],
            'spaceId' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * Get the validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => 'Ya existe una tarjeta con ese nombre', 'name.*' => 'Nombre requerido', 'spaceId.*' => 'Espacio inválido'];
    }

    /**
     * Trim the name so a blank name fails as required and duplicates are detected.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }
}
