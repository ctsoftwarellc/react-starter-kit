<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\ProcessDefinitionData;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\ProcessDefinition;

class CreateProcessDefinition
{
    public function execute(Environment $environment, ProcessDefinitionData $data): ProcessDefinition
    {
        $processDefinition = ProcessDefinition::create([
            'environment_id' => $environment->id,
            'type' => $data->type,
            'command' => $data->command,
            'instances' => $data->instances,
        ]);

        event(new EnvironmentConfigChanged($environment, 'process_created', [
            'type' => $processDefinition->type->value,
            'instances' => $processDefinition->instances,
        ]));

        return $processDefinition;
    }
}
