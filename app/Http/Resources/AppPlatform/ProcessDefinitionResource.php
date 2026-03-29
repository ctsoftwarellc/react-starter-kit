<?php

namespace App\Http\Resources\AppPlatform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'type' => $this->type->value,
            'command' => $this->command,
            'instances' => $this->instances,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
