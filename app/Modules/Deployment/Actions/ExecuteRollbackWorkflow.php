<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Services\RollbackManager;

class ExecuteRollbackWorkflow
{
    public function execute(Deployment $rollbackDeployment, ?Deployment $failedDeployment = null): Deployment
    {
        return (new RollbackManager)->execute($rollbackDeployment, $failedDeployment);
    }
}
