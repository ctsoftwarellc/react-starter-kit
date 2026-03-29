<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\EnvironmentVariable;

class DeleteEnvironmentVariable
{
    public function execute(EnvironmentVariable $variable): void
    {
        $environment = $variable->environment;
        $key = $variable->key;

        $variable->delete();

        event(new EnvironmentConfigChanged($environment, 'variable_deleted', [
            'key' => $key,
        ]));
    }
}
