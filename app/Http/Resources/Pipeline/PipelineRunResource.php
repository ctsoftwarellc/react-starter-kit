<?php

namespace App\Http\Resources\Pipeline;

use App\Http\Resources\AppPlatform\EnvironmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $durationSeconds = null;

        if ($this->started_at !== null) {
            $durationSeconds = $this->started_at->diffInSeconds($this->finished_at ?? now());
        }

        return [
            'id' => $this->id,
            'pipeline_id' => $this->pipeline_id,
            'environment_id' => $this->environment_id,
            'status' => $this->status->value,
            'trigger_type' => $this->trigger_type->value,
            'trigger_ref' => $this->trigger_ref,
            'trigger_sha' => $this->trigger_sha,
            'trigger_actor' => $this->trigger_actor,
            'definition_snapshot' => $this->definition_snapshot,
            'duration_seconds' => $durationSeconds,
            'pipeline' => new PipelineResource($this->whenLoaded('pipeline')),
            'environment' => new EnvironmentResource($this->whenLoaded('environment')),
            'jobs' => PipelineJobResource::collection($this->whenLoaded('jobs')),
            'artifact' => new ArtifactResource($this->whenLoaded('artifact')),
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
