<?php

namespace App\Modules\AppPlatform\Listeners;

use App\Modules\AppPlatform\Actions\CreateEnvironment;
use App\Modules\AppPlatform\DTOs\CreateEnvironmentData;
use App\Modules\AppPlatform\Enums\EnvironmentType;
use App\Modules\AppPlatform\Events\ApplicationCreated;

class CreateDefaultEnvironment
{
    public function handle(ApplicationCreated $event): void
    {
        if ($event->defaultClusterId === null) {
            return;
        }

        if ($event->application->environments()->where('name', 'production')->exists()) {
            return;
        }

        (new CreateEnvironment)->execute(
            $event->application,
            new CreateEnvironmentData(
                clusterId: $event->defaultClusterId,
                name: 'production',
                type: EnvironmentType::Production,
                isAutoDeploy: false,
                branch: $event->application->repository_branch,
            ),
        );
    }
}
