<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;

class ApplyRuntimeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'runtime_profile_id' => ['required', 'ulid', 'exists:runtime_profiles,id'],
        ];
    }
}
