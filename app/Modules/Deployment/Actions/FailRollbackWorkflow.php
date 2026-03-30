<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Services\RollbackManager;

class FailRollbackWorkflow
{
    public function execute(Deployment $rollbackDeployment, ?Deployment $failedDeployment = null, ?string $message = null): Deployment
    {
        return (new RollbackManager)->markRollbackFailed($rollbackDeployment, $failedDeployment, $message);
    }
}
