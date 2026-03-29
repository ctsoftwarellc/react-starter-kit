<?php

namespace App\Http\Resources\AppPlatform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnvironmentVariableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'key' => $this->key,
            'value' => $this->value,
            'is_build_arg' => $this->is_build_arg,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
