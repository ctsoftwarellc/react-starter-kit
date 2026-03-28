<?php

namespace App\Http\Requests\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'credentials' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
