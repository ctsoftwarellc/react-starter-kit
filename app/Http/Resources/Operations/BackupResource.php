<?php

namespace App\Http\Resources\Operations;

use App\Http\Resources\Infrastructure\ServerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'type' => $this->type,
            'status' => $this->status->value,
            'storage_path' => $this->storage_path,
            'size_bytes' => $this->size_bytes,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'retention_days' => $this->retention_days,
            'server' => new ServerResource($this->whenLoaded('server')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
