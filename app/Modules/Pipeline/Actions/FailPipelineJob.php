<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\DTOs\FailPipelineJobData;
use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Modules\Pipeline\Events\PipelineJobCompleted;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Services\PipelineOrchestrator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FailPipelineJob
{
    public function __construct(
        private readonly PipelineOrchestrator $orchestrator = new PipelineOrchestrator,
    ) {}

    public function execute(PipelineJob $job, FailPipelineJobData $data): PipelineJob
    {
        if (! in_array($data->status, [PipelineJobStatus::Failed, PipelineJobStatus::Cancelled, PipelineJobStatus::TimedOut], true)) {
            throw new InvalidArgumentException('Invalid failure status supplied for pipeline job.');
        }

        return DB::transaction(function () use ($job, $data) {
            $job = PipelineJob::query()->with(['runner', 'pipelineRun.jobs'])->lockForUpdate()->findOrFail($job->id);

            if ($job->status === PipelineJobStatus::Assigned) {
                $job->started_at ??= now();
                $job->save();
                $job->transitionTo(PipelineJobStatus::Running);
            }

            $job->forceFill([
                'started_at' => $job->started_at ?? now(),
                'finished_at' => now(),
                'exit_code' => $data->exitCode,
                'environment' => array_merge($job->environment ?? [], $data->metadata),
            ])->save();

            $job->transitionTo($data->status);

            if ($job->runner !== null && $job->runner->status !== RunnerStatus::Draining) {
                $job->runner->forceFill(['status' => RunnerStatus::Online])->save();
            }

            event(new PipelineJobCompleted($job->fresh(['pipelineRun', 'runner'])));
            $this->orchestrator->syncRunState($job->pipelineRun->fresh(['jobs']));

            return $job->fresh(['pipelineRun', 'runner']);
        });
    }
}
