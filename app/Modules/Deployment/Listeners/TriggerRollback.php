<?php

namespace App\Modules\Deployment\Listeners;

use App\Modules\Deployment\Actions\RollbackDeployment;
use App\Modules\Deployment\Events\DeploymentFailed;
use RuntimeException;

class TriggerRollback
{
    public function handle(DeploymentFailed $event): void
    {
        try {
            (new RollbackDeployment)->execute($event->deployment);
        } catch (RuntimeException) {
        }
    }
}
