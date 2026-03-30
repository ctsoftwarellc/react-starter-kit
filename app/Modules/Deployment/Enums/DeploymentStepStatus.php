<?php

namespace App\Modules\Deployment\Enums;

enum DeploymentStepStatus: string
{
    case Pending = 'pending';
    case Deploying = 'deploying';
    case Deployed = 'deployed';
    case HealthChecking = 'health_checking';
    case Active = 'active';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';
}
