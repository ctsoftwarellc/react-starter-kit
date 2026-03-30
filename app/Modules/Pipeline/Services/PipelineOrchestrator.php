<?php

namespace App\Modules\Pipeline\Services;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Events\PipelineRunCompleted;
use App\Modules\Pipeline\Events\PipelineRunStarted;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Support\Carbon;

class PipelineOrchestrator
{
    public function orchestrate(PipelineRun $run): PipelineRun
    {
        $run->loadMissing('jobs');

        if ($run->status === PipelineRunStatus::Pending) {
            $run->started_at ??= now();
            $run->save();
            $run->transitionTo(PipelineRunStatus::Running);
            event(new PipelineRunStarted($run->fresh()));
        }

        $this->ensureJobsExist($run->fresh(['jobs']));
        $this->queueReadyStageJobs($run->fresh(['jobs']));

        return $this->syncRunState($run->fresh(['jobs']));
    }

    public function queueReadyStageJobs(PipelineRun $run): void
    {
        $grouped = $run->jobs->groupBy('stage');
        $stageNames = $this->stageNames($run);

        foreach ($stageNames as $index => $stageName) {
            $jobs = $grouped->get($stageName, collect());

            if ($jobs->contains(fn (PipelineJob $job) => in_array($job->status, [PipelineJobStatus::Queued, PipelineJobStatus::Assigned, PipelineJobStatus::Running], true))) {
                return;
            }

            if ($jobs->every(fn (PipelineJob $job) => in_array($job->status, [PipelineJobStatus::Succeeded, PipelineJobStatus::Skipped], true))) {
                continue;
            }

            if ($this->previousStagesSucceeded($grouped, array_slice($stageNames, 0, $index))) {
                foreach ($jobs->filter(fn (PipelineJob $job) => $job->status === PipelineJobStatus::Pending) as $job) {
                    $job->transitionTo(PipelineJobStatus::Queued);
                }
            }

            return;
        }
    }

    public function syncRunState(PipelineRun $run): PipelineRun
    {
        $run->loadMissing('jobs');
        $jobs = $run->jobs;

        if ($jobs->contains(fn (PipelineJob $job) => in_array($job->status, [PipelineJobStatus::Queued, PipelineJobStatus::Assigned, PipelineJobStatus::Running], true))) {
            return $run;
        }

        if ($jobs->contains(fn (PipelineJob $job) => in_array($job->status, [PipelineJobStatus::Failed, PipelineJobStatus::Cancelled, PipelineJobStatus::TimedOut], true))) {
            foreach ($jobs->filter(fn (PipelineJob $job) => $job->status === PipelineJobStatus::Pending) as $job) {
                $job->transitionTo(PipelineJobStatus::Skipped);
            }

            return $this->completeRun($run->fresh(['jobs']), $this->terminalStatusFromJobs($jobs->all()));
        }

        if ($jobs->every(fn (PipelineJob $job) => in_array($job->status, [PipelineJobStatus::Succeeded, PipelineJobStatus::Skipped], true))) {
            return $this->completeRun($run, PipelineRunStatus::Succeeded);
        }

        return $run;
    }

    private function ensureJobsExist(PipelineRun $run): void
    {
        if ($run->jobs()->exists()) {
            return;
        }

        foreach ($run->definition_snapshot['stages'] as $stage) {
            foreach ($stage['jobs'] as $job) {
                $run->jobs()->create([
                    'stage' => $stage['name'],
                    'name' => $job['name'],
                    'status' => PipelineJobStatus::Pending,
                    'commands' => $job['commands'],
                    'environment' => $job['environment'] ?? [],
                ]);
            }
        }
    }

    private function stageNames(PipelineRun $run): array
    {
        return array_values(array_map(
            fn (array $stage) => $stage['name'],
            $run->definition_snapshot['stages'] ?? [],
        ));
    }

    private function previousStagesSucceeded($grouped, array $stages): bool
    {
        foreach ($stages as $stageName) {
            $jobs = $grouped->get($stageName, collect());

            if (! $jobs->every(fn (PipelineJob $job) => in_array($job->status, [PipelineJobStatus::Succeeded, PipelineJobStatus::Skipped], true))) {
                return false;
            }
        }

        return true;
    }

    private function terminalStatusFromJobs(array $jobs): PipelineRunStatus
    {
        foreach ($jobs as $job) {
            if ($job->status === PipelineJobStatus::TimedOut) {
                return PipelineRunStatus::TimedOut;
            }

            if ($job->status === PipelineJobStatus::Cancelled) {
                return PipelineRunStatus::Cancelled;
            }
        }

        return PipelineRunStatus::Failed;
    }

    private function completeRun(PipelineRun $run, PipelineRunStatus $status): PipelineRun
    {
        if ($run->status !== PipelineRunStatus::Running) {
            return $run;
        }

        $run->finished_at = Carbon::now();
        $run->save();
        $run->transitionTo($status);
        event(new PipelineRunCompleted($run->fresh()));

        return $run->fresh(['jobs']);
    }
}
