<?php

namespace App\Modules\Infrastructure\Enums;

enum AgentCommandStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';
}
