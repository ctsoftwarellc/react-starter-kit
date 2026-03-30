<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Models\Deployment;
use RuntimeException;

class CancelDeployment
{
    public function execute(Deployment $deployment): Deployment
    {
        if (! $deployment->canTransitionTo(DeploymentStatus::Cancelled)) {
            throw new RuntimeException('Only pending deployments can be cancelled.');
        }

        $deployment->finished_at = now();
        $deployment->save();
        $deployment->transitionTo(DeploymentStatus::Cancelled);

        return $deployment->fresh();
    }
}
