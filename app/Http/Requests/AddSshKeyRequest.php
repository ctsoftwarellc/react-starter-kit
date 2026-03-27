<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddSshKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'public_key' => ['required', 'string'],
        ];
    }
}
