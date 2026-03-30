<?php

namespace App\Modules\Deployment\Enums;

enum DeploymentStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case Deploying = 'deploying';
    case Verifying = 'verifying';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case RolledBack = 'rolled_back';
}
