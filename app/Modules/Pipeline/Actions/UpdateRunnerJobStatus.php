<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\DTOs\CompletePipelineJobData;
use App\Modules\Pipeline\DTOs\FailPipelineJobData;
use App\Modules\Pipeline\DTOs\UpdateRunnerJobStatusData;
use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateRunnerJobStatus
{
    public function execute(Runner $runner, PipelineJob $job, UpdateRunnerJobStatusData $data): PipelineJob
    {
        if ($job->runner_id !== $runner->id) {
            throw new HttpException(403, 'This job is not assigned to the authenticated runner.');
        }

        return match ($data->status) {
            PipelineJobStatus::Running => $this->markRunning($job),
            PipelineJobStatus::Succeeded => (new CompletePipelineJob)->execute($job, new CompletePipelineJobData(
                exitCode: $data->exitCode ?? 0,
                metadata: $data->metadata,
            )),
            default => (new FailPipelineJob)->execute($job, new FailPipelineJobData(
                status: $data->status,
                exitCode: $data->exitCode,
                metadata: $data->metadata,
            )),
        };
    }

    private function markRunning(PipelineJob $job): PipelineJob
    {
        return DB::transaction(function () use ($job) {
            $job = PipelineJob::query()->with(['pipelineRun', 'runner'])->lockForUpdate()->findOrFail($job->id);

            if ($job->status === PipelineJobStatus::Assigned) {
                $job->started_at ??= now();
                $job->save();
                $job->transitionTo(PipelineJobStatus::Running);
            }

            return $job->fresh(['pipelineRun', 'runner']);
        });
    }
}
