<?php

namespace App\Modules\ServiceManagement\Enums;

enum ServiceType: string
{
    case Database = 'database';
    case Cache = 'cache';
    case Storage = 'storage';
}
