<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Events\PipelineRunCompleted;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Support\Facades\DB;

class CancelPipelineRun
{
    public function execute(PipelineRun $run): PipelineRun
    {
        return DB::transaction(function () use ($run) {
            $run = PipelineRun::query()->with('jobs')->lockForUpdate()->findOrFail($run->id);

            foreach ($run->jobs as $job) {
                if ($job->status === PipelineJobStatus::Pending) {
                    $job->transitionTo(PipelineJobStatus::Skipped);
                }

                if (in_array($job->status->value, ['queued', 'assigned', 'running'], true)) {
                    $job->finished_at = now();
                    $job->save();
                    $job->transitionTo(PipelineJobStatus::Cancelled);
                }
            }

            if ($run->status === PipelineRunStatus::Pending) {
                $run->finished_at = now();
                $run->save();
                $run->transitionTo(PipelineRunStatus::Cancelled);
            }

            if ($run->status === PipelineRunStatus::Running) {
                $run->finished_at = now();
                $run->save();
                $run->transitionTo(PipelineRunStatus::Cancelled);
            }

            event(new PipelineRunCompleted($run->fresh()));

            return $run->fresh(['jobs']);
        });
    }
}
