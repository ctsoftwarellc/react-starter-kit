<?php

namespace App\Modules\Pipeline\Enums;

enum ArtifactStatus: string
{
    case Building = 'building';
    case Ready = 'ready';
    case Deployed = 'deployed';
    case Superseded = 'superseded';
    case Expired = 'expired';
    case Failed = 'failed';
}
