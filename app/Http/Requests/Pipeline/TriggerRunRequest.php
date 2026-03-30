<?php

namespace App\Http\Requests\Pipeline;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TriggerRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trigger_type' => ['sometimes', Rule::in(['push', 'tag', 'pull_request', 'manual', 'api', 'schedule'])],
            'environment_id' => ['nullable', 'exists:environments,id'],
            'trigger_ref' => ['nullable', 'string', 'max:255'],
            'trigger_sha' => ['nullable', 'string', 'max:40'],
            'trigger_actor' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('trigger_type')) {
            $this->merge(['trigger_type' => 'manual']);
        }
    }
}
