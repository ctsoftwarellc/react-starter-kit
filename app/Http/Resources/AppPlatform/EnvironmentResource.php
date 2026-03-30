<?php

namespace App\Http\Resources\AppPlatform;

use App\Http\Resources\Deployment\RemoteCommandResource;
use App\Http\Resources\Deployment\RuntimeProfileResource;
use App\Http\Resources\Deployment\ServerRoleProfileResource;
use App\Http\Resources\Infrastructure\ClusterResource;
use App\Http\Resources\Networking\DomainResource;
use App\Http\Resources\ServiceManagement\ServiceBindingResource;
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
            'runtime_profile_id' => $this->runtime_profile_id,
            'application' => new ApplicationResource($this->whenLoaded('application')),
            'cluster' => new ClusterResource($this->whenLoaded('cluster')),
            'runtime_profile' => new RuntimeProfileResource($this->whenLoaded('runtimeProfile')),
            'server_role_profiles' => ServerRoleProfileResource::collection($this->whenLoaded('serverRoleProfiles')),
            'remote_commands' => RemoteCommandResource::collection($this->whenLoaded('remoteCommands')),
            'variables' => EnvironmentVariableResource::collection($this->whenLoaded('variables')),
            'secrets' => SecretResource::collection($this->whenLoaded('secrets')),
            'process_definitions' => ProcessDefinitionResource::collection($this->whenLoaded('processDefinitions')),
            'service_bindings' => ServiceBindingResource::collection($this->whenLoaded('serviceBindings')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
