<?php

namespace App\Http\Resources\AppPlatform;

use App\Http\Resources\Infrastructure\ClusterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnvironmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'cluster_id' => $this->cluster_id,
            'name' => $this->name,
            'type' => $this->type->value,
            'is_auto_deploy' => $this->is_auto_deploy,
            'branch' => $this->branch,
            'application' => new ApplicationResource($this->whenLoaded('application')),
            'cluster' => new ClusterResource($this->whenLoaded('cluster')),
            'variables' => EnvironmentVariableResource::collection($this->whenLoaded('variables')),
            'secrets' => SecretResource::collection($this->whenLoaded('secrets')),
            'process_definitions' => ProcessDefinitionResource::collection($this->whenLoaded('processDefinitions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
