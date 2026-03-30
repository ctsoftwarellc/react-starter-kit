<?php

namespace App\Modules\Deployment\Enums;

enum RemoteCommandStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case TimedOut = 'timed_out';
}
