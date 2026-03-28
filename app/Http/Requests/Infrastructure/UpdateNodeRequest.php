<?php

namespace App\Http\Requests\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:web,worker,db,cache,queue,bastion'],
        ];
    }
}
