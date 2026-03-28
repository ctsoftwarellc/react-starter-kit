<?php

namespace App\Modules\Infrastructure\Enums;

enum NodeRole: string
{
    case Web = 'web';
    case Worker = 'worker';
    case Db = 'db';
    case Cache = 'cache';
    case Queue = 'queue';
    case Bastion = 'bastion';
}
