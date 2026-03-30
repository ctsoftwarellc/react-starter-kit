<?php

namespace App\Http\Resources\Deployment;

use App\Http\Resources\Infrastructure\ServerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deployment_id' => $this->deployment_id,
            'server_id' => $this->server_id,
            'status' => $this->status->value,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'output' => $this->output,
            'server' => new ServerResource($this->whenLoaded('server')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
