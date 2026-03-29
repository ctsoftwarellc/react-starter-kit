<?php

namespace App\Http\Requests\ServiceManagement;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BindServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $environment = $this->route('environment');
        $binding = $this->route('serviceBinding');

        return [
            'service_type' => ['required', Rule::in(['database', 'cache', 'storage'])],
            'binding_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('service_bindings', 'binding_name')
                    ->where(fn ($query) => $query->where('environment_id', $environment instanceof Environment ? $environment->id : null))
                    ->ignore($binding instanceof ServiceBinding ? $binding->id : null),
            ],
            'database_instance_id' => ['nullable', 'required_if:service_type,database', 'exists:database_instances,id'],
            'cache_instance_id' => ['nullable', 'required_if:service_type,cache', 'exists:cache_instances,id'],
            'storage_bucket_id' => ['nullable', 'required_if:service_type,storage', 'exists:storage_buckets,id'],
            'config' => ['sometimes', 'array'],
        ];
    }
}
