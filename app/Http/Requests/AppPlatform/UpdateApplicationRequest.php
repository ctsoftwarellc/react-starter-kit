<?php

namespace App\Http\Requests\AppPlatform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'runtime' => ['sometimes', Rule::in(['php', 'node', 'python', 'go'])],
            'repository_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'repository_branch' => ['sometimes', 'nullable', 'string', 'max:255'],
            'git_connection_id' => ['sometimes', 'nullable', 'exists:git_connections,id'],
            'settings' => ['sometimes', 'array'],
        ];
    }
}
