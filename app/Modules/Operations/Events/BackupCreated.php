<?php

namespace App\Modules\Operations\Events;

use App\Modules\Operations\Models\Backup;

class BackupCreated
{
    public function __construct(public Backup $backup) {}
}
