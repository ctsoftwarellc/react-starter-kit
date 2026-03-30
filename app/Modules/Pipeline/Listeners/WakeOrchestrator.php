<?php

namespace App\Modules\Pipeline\Listeners;

use App\Modules\Pipeline\Events\PipelineJobCompleted;
use App\Modules\Pipeline\Jobs\OrchestrateRun;
use App\Support\Enums\QueueName;

class WakeOrchestrator
{
    public function handle(PipelineJobCompleted $event): void
    {
        OrchestrateRun::dispatch($event->pipelineJob->pipelineRun)->onQueue(QueueName::Pipeline->value);
    }
}
