<?php

namespace App\Http\Requests\Installment;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Installment\InstallmentModel;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Requests\Concerns\ValidatesInstallmentTrait;

class UpdateInstallmentFormRequest extends FormRequest
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
     * Get the validation rules that apply to the request; every field is optional (partial update).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->installmentRules(true);
    }

    /**
     * Configure the validator: the cross-field checks run on the stored installment merged with the sent fields.
     *
     * @return array<int, callable> The after-validation callbacks.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $current = InstallmentModel::query()->find((int) $this->route('installmentId'));
            if ($current === null) {
                return; // The service answers 404.
            }

            $this->checkInstallment($validator, array_replace([
                'totalInstallments' => $current->total_installments,
                'paidInstallments' => $current->paid_installments,
                'type' => $current->type,
                'installmentAmount' => $current->installment_amount,
            ], $this->only(['totalInstallments', 'paidInstallments', 'type', 'installmentAmount', 'initialBalance'])));
        }];
    }
}
