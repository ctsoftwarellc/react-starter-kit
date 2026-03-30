<?php

namespace App\Modules\Deployment\Events;

use App\Modules\Deployment\Models\Deployment;

class DeploymentCompleted
{
    public function __construct(public Deployment $deployment) {}
}
