<?php

namespace App\Modules\Operations\Actions;

use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Models\Backup;
use App\Modules\Operations\Services\BackupExecutor;
use RuntimeException;

class RestoreBackup
{
    public function __construct(
        private readonly BackupExecutor $backupExecutor = new BackupExecutor,
    ) {}

    public function execute(Backup $backup, Server $server): Backup
    {
        $backup->loadMissing('server.clusters');
        $server->loadMissing('clusters');

        if (! $this->isCompatibleTarget($backup, $server)) {
            throw new RuntimeException('Backup restore target is not compatible with this backup.');
        }

        $this->backupExecutor->restore($backup, $server);

        if (! $this->backupExecutor->verifyRestore($backup, $server)) {
            throw new RuntimeException('Backup restore verification failed.');
        }

        return $backup->fresh();
    }

    private function isCompatibleTarget(Backup $backup, Server $targetServer): bool
    {
        $sourceServer = $backup->server;

        if ($sourceServer === null) {
            return false;
        }

        if ($backup->type === 'database') {
            $sourceEngine = $this->backupExecutor->detectDatabaseEngine($sourceServer);
            $targetEngine = $this->backupExecutor->detectDatabaseEngine($targetServer);

            return $sourceEngine !== 'unknown'
                && $sourceEngine === $targetEngine;
        }

        if ($sourceServer->id === $targetServer->id) {
            return true;
        }

        $sourceClusterIds = $sourceServer->clusters->pluck('id');

        return $targetServer->clusters->pluck('id')->intersect($sourceClusterIds)->isNotEmpty();
    }
}
