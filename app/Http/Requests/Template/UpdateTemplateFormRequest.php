<?php

namespace App\Http\Requests\Template;

use App\Models\Template\TemplateModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Http\Requests\Concerns\ValidatesTemplateTrait;

class UpdateTemplateFormRequest extends FormRequest
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
     * Get the validation rules that apply to the request; every field is optional (partial update).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->templateRules(true);
    }

    /**
     * Configure the validator: the half check runs on the stored template merged with the sent fields.
     *
     * @return array<int, callable> The after-validation callbacks.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $current = TemplateModel::query()->find((int) $this->route('templateId'));
            if ($current === null) {
                return; // The service answers 404.
            }

            $this->checkHalf(
                $validator,
                $this->has('frequency') ? $this->input('frequency') : $current->frequency,
                $this->has('half') ? $this->input('half') : $current->half,
            );
        }];
    }
}
