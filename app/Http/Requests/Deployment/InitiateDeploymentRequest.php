<?php

namespace App\Http\Requests\Deployment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InitiateDeploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artifact_id' => ['nullable', 'ulid', 'exists:artifacts,id'],
            'release_id' => ['nullable', 'ulid', 'exists:releases,id'],
            'strategy' => ['required', Rule::in(['rolling'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('strategy')) {
            $this->merge(['strategy' => 'rolling']);
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $targets = collect([
                    $this->input('artifact_id'),
                    $this->input('release_id'),
                ])->filter(fn (?string $value) => filled($value));

                if ($targets->count() !== 1) {
                    $validator->errors()->add('artifact_id', 'Provide exactly one deployment target.');
                }
            },
        ];
    }
}
