<?php

namespace App\Modules\AppPlatform\Listeners;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Events\ApplicationCreated;
use App\Modules\Pipeline\Actions\SetupWebhook;

class SetupApplicationWebhook
{
    public function handle(ApplicationCreated $event): void
    {
        if ($event->application->git_connection_id === null || $event->application->repository_url === null) {
            return;
        }

        (new SetupWebhook)->execute($event->application, GitProvider::Github);
    }
}
