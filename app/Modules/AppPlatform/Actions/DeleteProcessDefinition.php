<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\ProcessDefinition;

class DeleteProcessDefinition
{
    public function execute(ProcessDefinition $processDefinition): void
    {
        $environment = $processDefinition->environment;
        $type = $processDefinition->type->value;

        $processDefinition->delete();

        event(new EnvironmentConfigChanged($environment, 'process_deleted', [
            'type' => $type,
        ]));
    }
}
