<?php

namespace App\Http\Resources\Deployment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerRoleProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'role' => $this->role->value,
            'name' => $this->name,
            'config' => $this->config ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
