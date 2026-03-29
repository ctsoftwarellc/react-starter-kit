<?php

namespace App\Http\Requests\AppPlatform;

use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $environment = $this->route('environment');

        return [
            'cluster_id' => ['sometimes', 'exists:clusters,id'],
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('environments', 'name')
                    ->where(
                        fn ($query) => $query->where('application_id', $environment instanceof Environment ? $environment->application_id : null),
                    )
                    ->ignore($environment instanceof Environment ? $environment->id : null),
            ],
            'type' => ['sometimes', Rule::in(['production', 'staging', 'preview'])],
            'is_auto_deploy' => ['sometimes', 'boolean'],
            'branch' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
