<?php

namespace App\Modules\Deployment\Enums;

enum ReleaseStatus: string
{
    case Pending = 'pending';
    case Deploying = 'deploying';
    case Active = 'active';
    case Superseded = 'superseded';
    case RolledBack = 'rolled_back';
    case Failed = 'failed';
}
