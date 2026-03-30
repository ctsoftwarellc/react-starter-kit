<?php

namespace App\Http\Resources\Pipeline;

use App\Http\Resources\AppPlatform\ApplicationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'name' => $this->name,
            'definition' => $this->definition,
            'is_active' => $this->is_active,
            'trigger_branches' => $this->trigger_branches,
            'trigger_events' => $this->trigger_events,
            'application' => new ApplicationResource($this->whenLoaded('application')),
            'runs' => PipelineRunResource::collection($this->whenLoaded('runs')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
