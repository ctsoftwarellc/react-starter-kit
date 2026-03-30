<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\Pipeline\DTOs\PipelineDefinitionData;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Services\PipelineDefinitionValidator;

class UpdatePipeline
{
    public function __construct(
        private readonly PipelineDefinitionValidator $validator = new PipelineDefinitionValidator,
    ) {}

    public function execute(Pipeline $pipeline, PipelineDefinitionData $data): Pipeline
    {
        $pipeline->update([
            'name' => $data->name,
            'definition' => $this->validator->validate($data->definition),
            'is_active' => $data->isActive,
            'trigger_branches' => $data->triggerBranches,
            'trigger_events' => $data->triggerEvents,
        ]);

        return $pipeline->fresh();
    }
}
