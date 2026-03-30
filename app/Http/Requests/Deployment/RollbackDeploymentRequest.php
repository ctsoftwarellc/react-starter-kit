<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;

class RollbackDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'release_id' => ['required', 'ulid', 'exists:releases,id'],
        ];
    }
}
