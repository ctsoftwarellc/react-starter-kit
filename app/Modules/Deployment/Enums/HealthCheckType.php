<?php

namespace App\Modules\Deployment\Enums;

enum HealthCheckType: string
{
    case Http = 'http';
    case Tcp = 'tcp';
    case Command = 'command';
}
