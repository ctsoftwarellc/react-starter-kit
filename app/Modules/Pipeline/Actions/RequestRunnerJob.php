<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\Runner;
use RuntimeException;

class RequestRunnerJob
{
    public function execute(Runner $runner): ?PipelineJob
    {
        $job = PipelineJob::query()
            ->runnable()
            ->with(['pipelineRun.pipeline.application'])
            ->oldest('created_at')
            ->first();

        if ($job === null) {
            return null;
        }

        try {
            return (new AssignJobToRunner)->execute($job, $runner);
        } catch (RuntimeException) {
            return null;
        }
    }
}
