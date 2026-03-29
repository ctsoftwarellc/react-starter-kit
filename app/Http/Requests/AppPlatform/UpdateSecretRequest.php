<?php

namespace App\Http\Requests\AppPlatform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['sometimes', 'string', 'max:255'],
            'value' => ['sometimes', 'string'],
        ];
    }
}
