<?php

namespace App\Modules\Pipeline\Enums;

enum PipelineJobStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Assigned = 'assigned';
    case Running = 'running';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case TimedOut = 'timed_out';
    case Skipped = 'skipped';
}
