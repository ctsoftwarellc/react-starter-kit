<?php

namespace App\Http\Requests\AppPlatform;

use App\Modules\AppPlatform\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $application = $this->route('application');

        return [
            'cluster_id' => ['required', 'exists:clusters,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('environments', 'name')->where(
                    fn ($query) => $query->where('application_id', $application instanceof Application ? $application->id : null),
                ),
            ],
            'type' => ['required', Rule::in(['production', 'staging', 'preview'])],
            'is_auto_deploy' => ['sometimes', 'boolean'],
            'branch' => ['nullable', 'string', 'max:255'],
        ];
    }
}
