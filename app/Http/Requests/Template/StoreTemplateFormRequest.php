<?php

namespace App\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Requests\Concerns\ValidatesTemplateTrait;

class StoreTemplateFormRequest extends FormRequest
{
    use ValidatesTemplateTrait;

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
        return $this->templateRules(false);
    }

    /**
     * Configure the validator with the day-of-month check.
     *
     * @return array<int, callable> The after-validation callbacks.
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkDayOfMonth($validator, $this->input('frequency'), $this->input('dayOfMonth'))];
    }
}
