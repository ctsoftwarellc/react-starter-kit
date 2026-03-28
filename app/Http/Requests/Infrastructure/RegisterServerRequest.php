<?php

namespace App\Http\Requests\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class RegisterServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'hostname' => ['required', 'string', 'max:255'],
            'public_ip' => ['required', 'ip'],
            'private_ip' => ['nullable', 'ip'],
            'ssh_port' => ['sometimes', 'integer', 'min:1', 'max:65535'],
            'ssh_user' => ['sometimes', 'string', 'max:50'],
            'provider_id' => ['nullable', 'exists:providers,id'],
            'os' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
        ];
    }
}
