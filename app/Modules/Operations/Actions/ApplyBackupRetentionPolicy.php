<?php

namespace App\Modules\Operations\Actions;

use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Models\Backup;
use App\Support\Services\ObjectStorage\ObjectStorageService;

class ApplyBackupRetentionPolicy
{
    public function __construct(
        private readonly ObjectStorageService $storage = new ObjectStorageService,
    ) {}

    public function execute(): int
    {
        $expiredCount = 0;

        Backup::query()
            ->where('status', BackupStatus::Completed)
            ->get()
            ->filter(fn (Backup $backup) => $backup->created_at !== null && $backup->created_at->addDays($backup->retention_days)->isPast())
            ->each(function (Backup $backup) use (&$expiredCount): void {
                if ($backup->storage_path !== null && $this->storage->exists($backup->storage_path)) {
                    $this->storage->delete($backup->storage_path);
                }

                $backup->transitionTo(BackupStatus::Expired);
                $expiredCount++;
            });

        return $expiredCount;
    }
}
