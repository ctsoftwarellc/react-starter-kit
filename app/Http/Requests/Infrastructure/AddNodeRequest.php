<?php

namespace App\Http\Requests\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class AddNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'server_id' => ['required', 'exists:servers,id'],
            'role' => ['required', 'string', 'in:web,worker,db,cache,queue,bastion'],
        ];
    }
}
