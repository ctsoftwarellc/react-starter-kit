<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssignJobToRunner
{
    public function execute(PipelineJob $job, Runner $runner): PipelineJob
    {
        return DB::transaction(function () use ($job, $runner) {
            $job = PipelineJob::query()->lockForUpdate()->findOrFail($job->id);
            $runner = Runner::query()->lockForUpdate()->findOrFail($runner->id);

            if ($job->status !== PipelineJobStatus::Queued) {
                throw new RuntimeException('Only queued jobs can be assigned to a runner.');
            }

            if ($runner->status !== RunnerStatus::Online) {
                throw new RuntimeException('Runner is not available for assignment.');
            }

            $job->forceFill(['runner_id' => $runner->id])->save();
            $job->transitionTo(PipelineJobStatus::Assigned);
            $runner->forceFill(['status' => RunnerStatus::Busy])->save();

            return $job->fresh(['runner']);
        });
    }
}
