<?php

namespace App\Modules\ServiceManagement\Enums;

enum DatabaseEngine: string
{
    case Postgres = 'postgres';
    case Mysql = 'mysql';
}
