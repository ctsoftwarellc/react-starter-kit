<?php

namespace App\Http\Requests\Networking;

use Illuminate\Foundation\Http\FormRequest;

class AssignDomainRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'hostname' => strtolower(trim((string) $this->input('hostname'))),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hostname' => [
                'required',
                'string',
                'ascii',
                'max:255',
                'regex:/^(?=.{1,253}$)(?!-)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))+$/',
            ],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
