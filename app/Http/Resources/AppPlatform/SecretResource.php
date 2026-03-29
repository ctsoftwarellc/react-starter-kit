<?php

namespace App\Http\Resources\AppPlatform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecretResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'key' => $this->key,
            'version' => $this->version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
