<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Models\Pipeline;
use RuntimeException;

class DeletePipeline
{
    public function execute(Pipeline $pipeline): void
    {
        $hasActiveRun = $pipeline->runs()
            ->whereIn('status', [PipelineRunStatus::Pending->value, PipelineRunStatus::Running->value])
            ->exists();

        if ($hasActiveRun) {
            throw new RuntimeException('Cannot delete a pipeline with an active run.');
        }

        $pipeline->delete();
    }
}
