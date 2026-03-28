<?php

namespace App\Modules\Infrastructure\Enums;

enum ServerStatus: string
{
    case Pending = 'pending';
    case Provisioning = 'provisioning';
    case Bootstrapping = 'bootstrapping';
    case Active = 'active';
    case Draining = 'draining';
    case Cordoned = 'cordoned';
    case Maintenance = 'maintenance';
    case Decommissioning = 'decommissioning';
    case Decommissioned = 'decommissioned';
    case Failed = 'failed';
}
