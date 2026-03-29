<?php

namespace App\Http\Requests\AppPlatform;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $environment = $this->route('environment');
        $secret = $this->route('secret');

        return [
            'key' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('secrets', 'key')
                    ->where(
                        fn ($query) => $query->where('environment_id', $environment instanceof Environment ? $environment->id : null),
                    )
                    ->ignore($secret instanceof Secret ? $secret->id : null),
            ],
            'value' => ['sometimes', 'string'],
        ];
    }
}
