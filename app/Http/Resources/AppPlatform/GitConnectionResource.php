<?php

namespace App\Http\Resources\AppPlatform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GitConnectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider->value,
            'account_name' => $this->account_name,
            'token_expires_at' => $this->token_expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
