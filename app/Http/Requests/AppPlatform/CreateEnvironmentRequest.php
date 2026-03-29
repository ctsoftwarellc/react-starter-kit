<?php

namespace App\Http\Requests\AppPlatform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cluster_id' => ['required', 'exists:clusters,id'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['production', 'staging', 'preview'])],
            'is_auto_deploy' => ['sometimes', 'boolean'],
            'branch' => ['nullable', 'string', 'max:255'],
        ];
    }
}
