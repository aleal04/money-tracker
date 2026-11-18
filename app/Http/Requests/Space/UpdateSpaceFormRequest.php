<?php

namespace App\Http\Requests\Space;

use App\Models\Space\SpaceModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Requests\Concerns\ValidatesSpaceTrait;

class UpdateSpaceFormRequest extends FormRequest
{
    use ValidatesSpaceTrait;

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
        $rules = $this->spaceRules(true, (int) $this->route('spaceId'));
        unset($rules['isPrimary']); // The primary space only changes through make-primary.

        return $rules;
    }

    /**
     * Configure the validator: the custom-days check runs on the stored space merged with the sent fields.
     *
     * @return array<int, callable> The after-validation callbacks.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $current = SpaceModel::query()->find((int) $this->route('spaceId'));
            if ($current === null) {
                return; // The service answers 404.
            }

            $this->checkCustomDays(
                $validator,
                $this->has('frequency') ? $this->input('frequency') : $current->Frequency,
                $this->has('customDays') ? $this->input('customDays') : $current->CustomDays,
            );
        }];
    }
}
