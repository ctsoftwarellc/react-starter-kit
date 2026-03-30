<?php

namespace App\Modules\Deployment\Enums;

enum RuntimeProfileStack: string
{
    case PhpFpm = 'php-fpm';
    case Nginx = 'nginx';
    case Caddy = 'caddy';
    case Node = 'node';
    case Supervisor = 'supervisor';
}
