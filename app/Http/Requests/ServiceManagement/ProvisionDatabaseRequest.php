<?php

namespace App\Http\Requests\ServiceManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProvisionDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cluster_id' => ['required', 'exists:clusters,id'],
            'name' => ['required', 'string', 'max:255'],
            'engine' => ['required', Rule::in(['postgres', 'mysql'])],
            'version' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }
}
