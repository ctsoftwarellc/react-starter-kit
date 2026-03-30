<?php

namespace App\Http\Resources\Pipeline;

use App\Http\Resources\AppPlatform\ApplicationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtifactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pipeline_run_id' => $this->pipeline_run_id,
            'application_id' => $this->application_id,
            'status' => $this->status->value,
            'storage_path' => $this->storage_path,
            'content_hash' => $this->content_hash,
            'size_bytes' => $this->size_bytes,
            'metadata' => $this->metadata,
            'pipeline_run' => new PipelineRunResource($this->whenLoaded('pipelineRun')),
            'application' => new ApplicationResource($this->whenLoaded('application')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
