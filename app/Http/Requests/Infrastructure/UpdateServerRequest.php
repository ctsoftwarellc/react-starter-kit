<?php

namespace App\Http\Requests\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'hostname' => ['sometimes', 'string', 'max:255'],
            'ssh_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'ssh_user' => ['sometimes', 'string', 'max:50'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
