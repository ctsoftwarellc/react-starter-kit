<?php

namespace App\Http\Requests\AppPlatform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cluster_id' => ['sometimes', 'exists:clusters,id'],
            'name' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', Rule::in(['production', 'staging', 'preview'])],
            'is_auto_deploy' => ['sometimes', 'boolean'],
            'branch' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
