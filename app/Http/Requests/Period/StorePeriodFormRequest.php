<?php

namespace App\Http\Requests\Period;

use Illuminate\Foundation\Http\FormRequest;

class StorePeriodFormRequest extends FormRequest
{
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
            'spaceId' => ['sometimes', 'nullable', 'integer'],
            'startDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before:2101-01-01'],
            'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate', 'before:2101-01-01'],
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
            'spaceId.*' => 'Espacio inválido',
            'startDate.*' => 'Fecha de inicio inválida',
            'endDate.after_or_equal' => 'El fin debe ser igual o posterior al inicio',
            'endDate.*' => 'Fecha de fin inválida',
        ];
    }
}
