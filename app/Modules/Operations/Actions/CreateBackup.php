<?php

namespace App\Modules\Operations\Actions;

use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Events\BackupCreated;
use App\Modules\Operations\Jobs\ExecuteBackup as ExecuteBackupJob;
use App\Modules\Operations\Models\Backup;
use Illuminate\Support\Facades\DB;

class CreateBackup
{
    public function execute(Server $server, array $attributes): Backup
    {
        return DB::transaction(function () use ($server, $attributes) {
            $backup = $server->backups()->create([
                'type' => $attributes['type'],
                'status' => BackupStatus::Pending,
                'retention_days' => (int) ($attributes['retention_days'] ?? 30),
            ]);

            ExecuteBackupJob::dispatch($backup)->afterCommit();

            event(new BackupCreated($backup));

            return $backup->fresh();
        });
    }
}
