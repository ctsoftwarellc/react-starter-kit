<?php

namespace App\Http\Resources\Pipeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PipelineJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pipeline_run_id' => $this->pipeline_run_id,
            'stage' => $this->stage,
            'name' => $this->name,
            'status' => $this->status->value,
            'runner_id' => $this->runner_id,
            'commands' => $this->commands,
            'environment' => $this->environment,
            'log_path' => $this->log_path,
            'exit_code' => $this->exit_code,
            'pipeline_run' => new PipelineRunResource($this->whenLoaded('pipelineRun')),
            'runner' => new RunnerResource($this->whenLoaded('runner')),
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
