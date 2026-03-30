<?php

namespace App\Http\Resources\Deployment;

use App\Http\Resources\Infrastructure\ServerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemoteCommandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'server_id' => $this->server_id,
            'type' => $this->type->value,
            'command' => $this->command,
            'status' => $this->status->value,
            'output' => $this->output,
            'exit_code' => $this->exit_code,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'server' => new ServerResource($this->whenLoaded('server')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
