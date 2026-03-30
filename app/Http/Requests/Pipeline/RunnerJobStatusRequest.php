<?php

namespace App\Http\Requests\Pipeline;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunnerJobStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['running', 'succeeded', 'failed', 'cancelled', 'timed_out'])],
            'exit_code' => ['nullable', 'integer'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
