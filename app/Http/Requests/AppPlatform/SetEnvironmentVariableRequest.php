<?php

namespace App\Http\Requests\AppPlatform;

use Illuminate\Foundation\Http\FormRequest;

class SetEnvironmentVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string'],
            'is_build_arg' => ['sometimes', 'boolean'],
        ];
    }
}
