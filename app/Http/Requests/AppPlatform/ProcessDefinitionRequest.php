<?php

namespace App\Http\Requests\AppPlatform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['web', 'worker', 'scheduler', 'custom'])],
            'command' => ['required', 'string'],
            'instances' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
