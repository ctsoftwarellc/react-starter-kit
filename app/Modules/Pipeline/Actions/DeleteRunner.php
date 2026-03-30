<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Models\Runner;
use RuntimeException;

class DeleteRunner
{
    public function execute(Runner $runner): void
    {
        $hasActiveJob = $runner->pipelineJobs()
            ->whereIn('status', [
                PipelineJobStatus::Assigned->value,
                PipelineJobStatus::Running->value,
            ])
            ->exists();

        if ($hasActiveJob) {
            throw new RuntimeException('Cannot delete a runner with an active job.');
        }

        $runner->delete();
    }
}
