<?php

namespace App\Http\Resources\Pipeline;

use App\Modules\Pipeline\Models\PipelineJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RunnerJobAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PipelineJob $job */
        $job = $this->resource;
        $jobDefinition = $this->jobDefinition($job);

        return [
            'job_id' => $job->id,
            'pipeline_run_id' => $job->pipeline_run_id,
            'repository_url' => $job->pipelineRun?->pipeline?->application?->repository_url,
            'ref' => $job->pipelineRun?->trigger_ref,
            'sha' => $job->pipelineRun?->trigger_sha,
            'image' => $jobDefinition['image'] ?? null,
            'commands' => $job->commands,
            'environment' => $job->environment,
            'services' => $jobDefinition['services'] ?? [],
            'timeout_minutes' => $jobDefinition['timeout'] ?? null,
            'artifact_config' => ($job->pipelineRun?->definition_snapshot['artifact'] ?? false) ? ['enabled' => true] : null,
        ];
    }

    private function jobDefinition(PipelineJob $job): array
    {
        $stages = $job->pipelineRun?->definition_snapshot['stages'] ?? [];

        foreach ($stages as $stage) {
            if (($stage['name'] ?? null) !== $job->stage) {
                continue;
            }

            foreach ($stage['jobs'] ?? [] as $definition) {
                if (($definition['name'] ?? null) === $job->name) {
                    return $definition;
                }
            }
        }

        return [];
    }
}
