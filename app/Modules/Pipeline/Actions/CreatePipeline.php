<?php

namespace App\Modules\Pipeline\Actions;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\DTOs\PipelineDefinitionData;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Services\PipelineDefinitionValidator;

class CreatePipeline
{
    public function __construct(
        private readonly PipelineDefinitionValidator $validator = new PipelineDefinitionValidator,
    ) {}

    public function execute(Application $application, PipelineDefinitionData $data): Pipeline
    {
        $definition = $this->validator->validate($data->definition);

        return Pipeline::create([
            'application_id' => $application->id,
            'name' => $data->name,
            'definition' => $definition,
            'is_active' => $data->isActive,
            'trigger_branches' => $data->triggerBranches,
            'trigger_events' => $data->triggerEvents,
        ]);
    }
}
