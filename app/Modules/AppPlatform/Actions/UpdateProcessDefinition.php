<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\ProcessDefinitionData;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\ProcessDefinition;

class UpdateProcessDefinition
{
    public function execute(ProcessDefinition $processDefinition, ProcessDefinitionData $data): ProcessDefinition
    {
        $processDefinition->update([
            'type' => $data->type,
            'command' => $data->command,
            'instances' => $data->instances,
        ]);

        $processDefinition = $processDefinition->fresh();

        event(new EnvironmentConfigChanged($processDefinition->environment, 'process_updated', [
            'type' => $processDefinition->type->value,
            'instances' => $processDefinition->instances,
        ]));

        return $processDefinition;
    }
}
