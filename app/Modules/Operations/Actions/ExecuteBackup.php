<?php

namespace App\Modules\Operations\Actions;

use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Models\Backup;
use App\Modules\Operations\Services\BackupExecutor;
use Throwable;

class ExecuteBackup
{
    public function __construct(
        private readonly BackupExecutor $backupExecutor = new BackupExecutor,
    ) {}

    public function execute(Backup $backup): Backup
    {
        if ($backup->status !== BackupStatus::Pending) {
            return $backup->fresh();
        }

        $backup->transitionTo(BackupStatus::Running);
        $backup->update(['started_at' => now()]);

        try {
            $result = $backup->type === 'database'
                ? $this->backupExecutor->createDatabaseBackup($backup)
                : $this->backupExecutor->createFileBackup($backup);

            $backup->update([
                'storage_path' => $result['storage_path'],
                'size_bytes' => $result['size_bytes'],
                'finished_at' => now(),
            ]);

            $backup->transitionTo(BackupStatus::Completed);
        } catch (Throwable $exception) {
            $backup->update(['finished_at' => now()]);
            $backup->transitionTo(BackupStatus::Failed);

            throw $exception;
        }

        return $backup->fresh();
    }
}
