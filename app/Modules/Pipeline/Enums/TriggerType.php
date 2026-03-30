<?php

namespace App\Modules\Pipeline\Enums;

enum TriggerType: string
{
    case Push = 'push';
    case Tag = 'tag';
    case PullRequest = 'pull_request';
    case Manual = 'manual';
    case Api = 'api';
    case Schedule = 'schedule';
}
