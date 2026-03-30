<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServerRoleProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(['web', 'worker', 'db', 'cache', 'queue', 'bastion'])],
            'name' => ['required', 'string', 'max:100'],
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
