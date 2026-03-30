<?php

namespace App\Modules\Pipeline\Jobs;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Modules\Pipeline\Services\PipelineOrchestrator;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class OrchestrateRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public PipelineRun $pipelineRun) {}

    public function handle(): void
    {
        (new PipelineOrchestrator)->orchestrate($this->pipelineRun->fresh());
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        $run = $this->pipelineRun->fresh(['jobs']);

        if (! in_array($run->status, [PipelineRunStatus::Pending, PipelineRunStatus::Running], true)) {
            return;
        }

        foreach ($run->jobs as $job) {
            if ($job->status === PipelineJobStatus::Pending) {
                $job->transitionTo(PipelineJobStatus::Skipped);
            }

            if (in_array($job->status->value, ['queued', 'assigned'], true)) {
                $job->finished_at = now();
                $job->save();
                $job->transitionTo(PipelineJobStatus::Cancelled);
            }
        }

        if ($run->status === PipelineRunStatus::Pending) {
            $run->started_at ??= now();
            $run->save();
            $run->transitionTo(PipelineRunStatus::Running);
            $run = $run->fresh();
        }

        $run->finished_at = now();
        $run->save();
        $run->transitionTo(PipelineRunStatus::Failed);
    }

    public function queue(): string
    {
        return QueueName::Pipeline->value;
    }
}
