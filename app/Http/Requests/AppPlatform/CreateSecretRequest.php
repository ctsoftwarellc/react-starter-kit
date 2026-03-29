<?php

namespace App\Http\Requests\AppPlatform;

use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $environment = $this->route('environment');

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('secrets', 'key')->where(
                    fn ($query) => $query->where('environment_id', $environment instanceof Environment ? $environment->id : null),
                ),
            ],
            'value' => ['required', 'string'],
        ];
    }
}
