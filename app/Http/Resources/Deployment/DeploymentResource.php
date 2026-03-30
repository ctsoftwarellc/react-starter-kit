<?php

namespace App\Http\Resources\Deployment;

use App\Http\Resources\AppPlatform\EnvironmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'release_id' => $this->release_id,
            'environment_id' => $this->environment_id,
            'status' => $this->status->value,
            'strategy' => $this->strategy->value,
            'total_nodes' => $this->total_nodes,
            'completed_nodes' => $this->completed_nodes,
            'failed_nodes' => $this->failed_nodes,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'initiated_by' => $this->initiated_by,
            'release' => new ReleaseResource($this->whenLoaded('release')),
            'environment' => new EnvironmentResource($this->whenLoaded('environment')),
            'steps' => DeploymentStepResource::collection($this->whenLoaded('steps')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
