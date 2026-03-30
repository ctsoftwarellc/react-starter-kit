<?php

namespace App\Http\Resources\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RunnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'platform' => $this->platform,
            'metadata' => $this->metadata,
            'last_heartbeat_at' => $this->last_heartbeat_at,
            'current_job' => $this->whenLoaded('pipelineJobs', function () {
                $job = $this->pipelineJobs->first();

                if ($job === null) {
                    return null;
                }

                return [
                    'id' => $job->id,
                    'pipeline_run_id' => $job->pipeline_run_id,
                    'stage' => $job->stage,
                    'name' => $job->name,
                    'status' => $job->status->value,
                    'pipeline' => $job->relationLoaded('pipelineRun') && $job->pipelineRun?->relationLoaded('pipeline')
                        ? [
                            'id' => $job->pipelineRun->pipeline?->id,
                            'name' => $job->pipelineRun->pipeline?->name,
                        ]
                        : null,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
