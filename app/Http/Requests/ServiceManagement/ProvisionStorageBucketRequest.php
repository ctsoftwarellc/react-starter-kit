<?php

namespace App\Http\Requests\ServiceManagement;

use Illuminate\Foundation\Http\FormRequest;

class ProvisionStorageBucketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'bucket_name' => ['required', 'string', 'max:255'],
        ];
    }
}
