<?php

namespace App\Http\Requests\Installment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Requests\Concerns\ValidatesInstallmentTrait;

class StoreInstallmentFormRequest extends FormRequest
{
    use ValidatesInstallmentTrait;

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
        return $this->installmentRules(false);
    }

    /**
     * Configure the validator with the cross-field checks.
     *
     * @return array<int, callable> The after-validation callbacks.
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkInstallment($validator, $this->all())];
    }
}
