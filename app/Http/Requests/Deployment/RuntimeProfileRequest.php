<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RuntimeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'stack' => ['required', Rule::in(['php-fpm', 'nginx', 'caddy', 'node', 'supervisor'])],
            'config' => ['sometimes', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $config = $this->input('config');

        if (is_string($config) && $config !== '') {
            $decoded = json_decode($config, true);

            if (is_array($decoded)) {
                $this->merge(['config' => $decoded]);
            }
        }
    }
}
