<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\DTOs\TriggerPipelineRunData;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Jobs\OrchestrateRun;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Support\Enums\QueueName;
use Illuminate\Support\Facades\DB;

class TriggerPipelineRun
{
    public function execute(Pipeline $pipeline, TriggerPipelineRunData $data): PipelineRun
    {
        return DB::transaction(function () use ($pipeline, $data) {
            $run = PipelineRun::create([
                'pipeline_id' => $pipeline->id,
                'environment_id' => $data->environmentId,
                'status' => PipelineRunStatus::Pending,
                'trigger_type' => $data->triggerType,
                'trigger_ref' => $data->triggerRef,
                'trigger_sha' => $data->triggerSha,
                'trigger_actor' => $data->triggerActor,
                'definition_snapshot' => $pipeline->definition,
            ]);

            DB::afterCommit(fn () => OrchestrateRun::dispatch($run)->onQueue(QueueName::Pipeline->value));

            return $run;
        });
    }
}
