<?php

namespace App\Http\Resources\AppPlatform;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'runtime' => $this->runtime->value,
            'repository_url' => $this->repository_url,
            'repository_branch' => $this->repository_branch,
            'git_connection_id' => $this->git_connection_id,
            'settings' => $this->settings,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'git_connection' => new GitConnectionResource($this->whenLoaded('gitConnection')),
            'environments' => EnvironmentResource::collection($this->whenLoaded('environments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
