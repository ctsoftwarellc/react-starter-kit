<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertHealthCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'ulid', 'exists:health_checks,id'],
            'type' => ['required', Rule::in(['http'])],
            'target' => ['required', 'string', 'max:500'],
            'interval_seconds' => ['required', 'integer', 'min:5'],
            'timeout_seconds' => ['required', 'integer', 'min:1'],
            'healthy_threshold' => ['required', 'integer', 'min:1'],
            'unhealthy_threshold' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
