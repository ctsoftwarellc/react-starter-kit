<?php

namespace App\Http\Requests\AppPlatform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'runtime' => ['required', Rule::in(['php', 'node', 'python', 'go'])],
            'repository_url' => ['nullable', 'url', 'max:500'],
            'repository_branch' => ['sometimes', 'string', 'max:255'],
            'git_connection_id' => ['nullable', 'exists:git_connections,id'],
            'settings' => ['sometimes', 'array'],
            'default_cluster_id' => ['nullable', 'exists:clusters,id'],
        ];
    }
}
