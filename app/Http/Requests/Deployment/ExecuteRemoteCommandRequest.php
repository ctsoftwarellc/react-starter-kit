<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecuteRemoteCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'server_id' => ['sometimes', 'nullable', 'ulid', 'exists:servers,id'],
            'type' => ['required', Rule::in(['run_migrations', 'clear_cache', 'restart_workers', 'artisan_tinker', 'custom'])],
            'command' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'script' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'allow_arbitrary' => ['sometimes', 'boolean'],
        ];
    }
}
