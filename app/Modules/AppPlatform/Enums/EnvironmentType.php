<?php

namespace App\Modules\AppPlatform\Enums;

enum EnvironmentType: string
{
    case Production = 'production';
    case Staging = 'staging';
    case Preview = 'preview';
}
