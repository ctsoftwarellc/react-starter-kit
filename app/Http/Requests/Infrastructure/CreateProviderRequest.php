<?php

namespace App\Http\Requests\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class CreateProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:digitalocean,hetzner,vultr,aws,manual'],
            'credentials' => ['required', 'array'],
        ];
    }
}
