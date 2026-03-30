<?php

namespace App\Modules\Operations\Services;

use App\Modules\Infrastructure\Models\Server;
use App\Modules\Infrastructure\Services\SshService;
use App\Modules\Operations\Models\Backup;
use App\Support\Services\ObjectStorage\ObjectStorageService;
use Illuminate\Support\Arr;
use RuntimeException;

class BackupExecutor
{
    public function __construct(
        private readonly ObjectStorageService $storage = new ObjectStorageService,
    ) {}

    public function createDatabaseBackup(Backup $backup): array
    {
        $backup->loadMissing('server');

        $server = $backup->server;

        if ($server === null) {
            throw new RuntimeException('Backup server is required.');
        }

        $engine = $this->detectDatabaseEngine($server);

        if ($engine === 'unknown') {
            throw new RuntimeException('Unable to determine the database engine for backup execution.');
        }

        $remotePath = $this->remotePath($backup, 'sql.gz');
        $output = $this->runOnServer($server, $this->buildDatabaseBackupCommand($remotePath, $engine));
        $metadata = $this->parseReadyMarker($output, '__HELM_BACKUP_READY__');
        $contents = $this->downloadRemoteFile($server, $remotePath);
        $storagePath = $this->storagePath($backup, $engine === 'postgres' ? 'postgres.sql.gz' : 'mysql.sql.gz');

        $this->storage->putBackup($storagePath, $contents);
        $this->cleanupRemoteFile($server, $remotePath);

        return [
            'storage_path' => $storagePath,
            'size_bytes' => (int) ($metadata['size'] ?? strlen($contents)),
        ];
    }

    public function createFileBackup(Backup $backup): array
    {
        $backup->loadMissing('server');

        $server = $backup->server;

        if ($server === null) {
            throw new RuntimeException('Backup server is required.');
        }

        $remotePath = $this->remotePath($backup, 'tar.gz');
        $output = $this->runOnServer($server, $this->buildFileBackupCommand($remotePath));
        $metadata = $this->parseReadyMarker($output, '__HELM_BACKUP_READY__');
        $contents = $this->downloadRemoteFile($server, $remotePath);
        $storagePath = $this->storagePath($backup, 'files.tar.gz');

        $this->storage->putBackup($storagePath, $contents);
        $this->cleanupRemoteFile($server, $remotePath);

        return [
            'storage_path' => $storagePath,
            'size_bytes' => (int) ($metadata['size'] ?? strlen($contents)),
        ];
    }

    public function restore(Backup $backup, Server $targetServer): void
    {
        if ($backup->storage_path === null || ! $this->storage->exists($backup->storage_path)) {
            throw new RuntimeException('Backup payload is not available in object storage.');
        }

        $payload = $this->storage->getBackup($backup->storage_path);

        if ($payload === null) {
            throw new RuntimeException('Backup payload could not be read from object storage.');
        }

        $remotePath = $this->remotePath($backup, $backup->type === 'database' ? 'restore.sql.gz' : 'restore.tar.gz');
        $restoreCommand = $this->buildRestoreCommand($backup, $targetServer, $remotePath);

        $this->withConnection($targetServer, function (SshService $ssh) use ($backup, $payload, $remotePath, $restoreCommand, $targetServer): void {
            $ssh->upload($remotePath, $payload);

            try {
                $output = $ssh->execute($restoreCommand);

                if (! str_contains($output, '__HELM_RESTORE_OK__')) {
                    throw new RuntimeException('Backup restore command did not report success.');
                }

                $verificationOutput = $ssh->execute($this->buildRestoreVerificationCommand($backup, $targetServer, $remotePath));

                if (! str_contains($verificationOutput, '__HELM_RESTORE_VERIFIED__')) {
                    throw new RuntimeException('Backup restore verification probe failed.');
                }
            } finally {
                $ssh->execute('rm -f '.escapeshellarg($remotePath));
            }
        });

        $this->storage->putBackup(
            $this->restoreReceiptPath($backup, $targetServer),
            json_encode([
                'backup_id' => $backup->id,
                'server_id' => $targetServer->id,
                'restored_at' => now()->toIso8601String(),
                'verified' => true,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    public function verifyRestore(Backup $backup, Server $targetServer): bool
    {
        $receiptPath = $this->restoreReceiptPath($backup, $targetServer);

        if (! $this->storage->exists($receiptPath)) {
            return false;
        }

        $payload = json_decode($this->storage->getBackup($receiptPath) ?? '[]', true);

        return Arr::get($payload, 'backup_id') === $backup->id
            && Arr::get($payload, 'server_id') === $targetServer->id
            && Arr::get($payload, 'verified') === true;
    }

    public function detectDatabaseEngine(Server $server): string
    {
        $server->loadMissing('clusters');

        $metadataEngine = data_get($server->metadata, 'database_engine')
            ?? data_get($server->metadata, 'db_engine');

        if (is_string($metadataEngine) && in_array($metadataEngine, ['postgres', 'mysql'], true)) {
            return $metadataEngine;
        }

        $output = $this->runOnServer(
            $server,
            'if command -v pg_dumpall >/dev/null 2>&1; then printf "postgres"; '
            .'elif command -v mysqldump >/dev/null 2>&1; then printf "mysql"; '
            .'else printf "unknown"; fi',
        );

        $engine = trim($output);

        return in_array($engine, ['postgres', 'mysql'], true) ? $engine : 'unknown';
    }

    private function buildDatabaseBackupCommand(string $remotePath, string $engine): string
    {
        $escapedPath = escapeshellarg($remotePath);

        $dumpCommand = $engine === 'mysql'
            ? 'sudo mysqldump --all-databases --single-transaction --quick'
            : 'sudo pg_dumpall --clean --if-exists';

        return $dumpCommand.' | gzip -c > '.$escapedPath
            .' && printf "__HELM_BACKUP_READY__:%s:%s" "$(stat -c%s '.$escapedPath.')" "$(sha256sum '.$escapedPath.' | cut -d\" \" -f1)"';
    }

    private function buildFileBackupCommand(string $remotePath): string
    {
        $escapedPath = escapeshellarg($remotePath);

        return "sudo sh -lc 'set --; for path in /var/www /srv /opt/helm /home/helm/app/storage/app/public; do [ -e \"\$path\" ] && set -- \"\$@\" \"\$path\"; done; [ \"\$#\" -gt 0 ] || exit 64; tar -czf \"{$remotePath}\" \"\$@\"'"
            .' && printf "__HELM_BACKUP_READY__:%s:%s" "$(stat -c%s '.$escapedPath.')" "$(sha256sum '.$escapedPath.' | cut -d\" \" -f1)"';
    }

    private function buildRestoreCommand(Backup $backup, Server $targetServer, string $remotePath): string
    {
        $escapedPath = escapeshellarg($remotePath);

        if ($backup->type === 'database') {
            $engine = $this->detectDatabaseEngine($targetServer);

            if ($engine === 'unknown') {
                throw new RuntimeException('Unable to determine the target database engine for restore execution.');
            }

            $restoreCommand = $engine === 'mysql'
                ? 'gunzip -c '.$escapedPath.' | sudo mysql'
                : 'gunzip -c '.$escapedPath.' | sudo psql postgres';

            return $restoreCommand.' && printf "__HELM_RESTORE_OK__"';
        }

        return 'sudo tar -xzf '.$escapedPath.' -C / && printf "__HELM_RESTORE_OK__"';
    }

    private function buildRestoreVerificationCommand(Backup $backup, Server $targetServer, string $remotePath): string
    {
        if ($backup->type === 'database') {
            $engine = $this->detectDatabaseEngine($targetServer);

            if ($engine === 'unknown') {
                throw new RuntimeException('Unable to determine the target database engine for restore verification.');
            }

            return $engine === 'mysql'
                ? 'sudo mysql -Nse "SELECT 1" >/dev/null && printf "__HELM_RESTORE_VERIFIED__"'
                : 'sudo psql postgres -Atqc "SELECT 1" >/dev/null && printf "__HELM_RESTORE_VERIFIED__"';
        }

        $escapedPath = escapeshellarg($remotePath);

        return "first_path=\$(tar -tzf {$escapedPath} | head -n 1); first_path=\${first_path#./}; [ -n \"\$first_path\" ] && test -e \"/\$first_path\" && printf \"__HELM_RESTORE_VERIFIED__\"";
    }

    private function parseReadyMarker(string $output, string $marker): array
    {
        $trimmed = trim($output);

        if (! str_contains($trimmed, $marker.':')) {
            throw new RuntimeException('Backup command did not report artifact metadata.');
        }

        $parts = explode(':', $trimmed);

        return [
            'size' => $parts[1] ?? null,
            'checksum' => $parts[2] ?? null,
        ];
    }

    private function remotePath(Backup $backup, string $suffix): string
    {
        return sprintf('/tmp/helm-backup-%s.%s', $backup->id, $suffix);
    }

    private function storagePath(Backup $backup, string $suffix): string
    {
        return sprintf(
            'backups/%s/%s-%s.%s',
            $backup->server_id,
            $backup->created_at?->format('YmdHis') ?? now()->format('YmdHis'),
            $backup->id,
            $suffix,
        );
    }

    private function restoreReceiptPath(Backup $backup, Server $targetServer): string
    {
        return sprintf('restores/%s/%s.json', $targetServer->id, $backup->id);
    }

    private function cleanupRemoteFile(Server $server, string $remotePath): void
    {
        $this->runOnServer($server, 'rm -f '.escapeshellarg($remotePath));
    }

    private function downloadRemoteFile(Server $server, string $remotePath): string
    {
        return $this->withConnection($server, fn (SshService $ssh): string => $ssh->download($remotePath));
    }

    private function runOnServer(Server $server, string $command): string
    {
        return $this->withConnection($server, fn (SshService $ssh): string => $ssh->execute($command));
    }

    private function withConnection(Server $server, callable $callback): mixed
    {
        $ssh = new SshService;
        $ssh->connect($server->public_ip, $server->ssh_port, $server->ssh_user);

        try {
            return $callback($ssh);
        } finally {
            $ssh->disconnect();
        }
    }
}
