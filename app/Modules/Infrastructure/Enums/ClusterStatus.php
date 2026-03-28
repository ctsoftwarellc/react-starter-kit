<?php

namespace App\Modules\Infrastructure\Enums;

enum ClusterStatus: string
{
    case Pending = 'pending';
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Updating = 'updating';
    case Scaling = 'scaling';
    case Degraded = 'degraded';
    case Maintenance = 'maintenance';
    case Decommissioning = 'decommissioning';
    case Decommissioned = 'decommissioned';
}
