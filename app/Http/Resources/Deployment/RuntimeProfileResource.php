<?php

namespace App\Http\Resources\Deployment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RuntimeProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'name' => $this->name,
            'stack' => $this->stack->value,
            'config' => $this->config ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
