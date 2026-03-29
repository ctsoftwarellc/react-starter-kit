<?php

namespace App\Modules\AppPlatform\Enums;

enum ProcessType: string
{
    case Web = 'web';
    case Worker = 'worker';
    case Scheduler = 'scheduler';
    case Custom = 'custom';
}
