<?php

namespace App\Modules\Pipeline\Enums;

enum RunnerStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Busy = 'busy';
    case Draining = 'draining';
}
