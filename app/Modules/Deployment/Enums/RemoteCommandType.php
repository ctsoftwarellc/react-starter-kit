<?php

namespace App\Modules\Deployment\Enums;

enum RemoteCommandType: string
{
    case RunMigrations = 'run_migrations';
    case ClearCache = 'clear_cache';
    case RestartWorkers = 'restart_workers';
    case ArtisanTinker = 'artisan_tinker';
    case Custom = 'custom';
}
