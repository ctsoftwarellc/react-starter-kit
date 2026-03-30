<?php

namespace App\Http\Requests\Pipeline;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePipelineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'definition' => ['required', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'trigger_branches' => ['sometimes', 'array'],
            'trigger_branches.*' => ['string', 'max:255'],
            'trigger_events' => ['sometimes', 'array'],
            'trigger_events.*' => ['string', Rule::in(['push', 'tag', 'pull_request', 'manual', 'api', 'schedule'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('definition'))) {
            $decoded = json_decode($this->input('definition'), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['definition' => $decoded]);
            }
        }
    }
}
