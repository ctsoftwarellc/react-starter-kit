<?php

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentCommandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'type' => $this->type->value,
            'payload' => $this->payload,
            'status' => $this->status->value,
            'result' => $this->result,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
