<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Services\DeploymentCoordinator;

class ExecuteDeploymentWorkflow
{
    public function execute(Deployment $deployment): Deployment
    {
        return (new DeploymentCoordinator)->execute($deployment);
    }
}
