<?php

namespace App\Modules\ServiceManagement\Enums;

enum CacheEngine: string
{
    case Redis = 'redis';
    case Valkey = 'valkey';
}
