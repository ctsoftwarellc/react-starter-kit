<?php

namespace App\Http\Resources\Deployment;

use App\Http\Resources\AppPlatform\EnvironmentResource;
use App\Http\Resources\Pipeline\ArtifactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'artifact_id' => $this->artifact_id,
            'version' => $this->version,
            'status' => $this->status->value,
            'config_snapshot' => $this->config_snapshot,
            'deployed_by' => $this->deployed_by,
            'environment' => new EnvironmentResource($this->whenLoaded('environment')),
            'artifact' => new ArtifactResource($this->whenLoaded('artifact')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
