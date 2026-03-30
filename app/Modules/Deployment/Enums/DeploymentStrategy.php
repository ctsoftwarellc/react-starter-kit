<?php

namespace App\Modules\Deployment\Enums;

enum DeploymentStrategy: string
{
    case Rolling = 'rolling';
    case BlueGreen = 'blue_green';
    case Canary = 'canary';
}
