<?php

namespace App\Modules\Infrastructure\Services;

use RuntimeException;

class SshService
{
    private mixed $connection = null;

    public function connect(string $host, int $port = 22, string $user = 'root'): void
    {
        $this->connection = @\ssh2_connect($host, $port);

        if ($this->connection === false) {
            throw new RuntimeException("Failed to connect to {$host}:{$port}");
        }

        $privateKeyPath = config('helm.ssh_private_key_path', storage_path('app/ssh/id_rsa'));

        if (! file_exists($privateKeyPath)) {
            throw new RuntimeException("SSH private key not found at {$privateKeyPath}");
        }

        $publicKeyPath = $privateKeyPath.'.pub';

        $authenticated = \ssh2_auth_pubkey_file(
            $this->connection,
            $user,
            $publicKeyPath,
            $privateKeyPath,
        );

        if (! $authenticated) {
            throw new RuntimeException("SSH authentication failed for {$user}@{$host}");
        }
    }

    public function execute(string $command): string
    {
        if ($this->connection === null) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }

        $stream = \ssh2_exec($this->connection, $command);

        if ($stream === false) {
            throw new RuntimeException("Failed to execute command: {$command}");
        }

        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        fclose($stream);

        return $output ?: '';
    }

    public function upload(string $remotePath, string $content): void
    {
        if ($this->connection === null) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }

        $sftp = \ssh2_sftp($this->connection);

        if ($sftp === false) {
            throw new RuntimeException('Failed to initialize SFTP subsystem.');
        }

        $stream = fopen("ssh2.sftp://{$sftp}{$remotePath}", 'w');

        if ($stream === false) {
            throw new RuntimeException("Failed to open remote file: {$remotePath}");
        }

        fwrite($stream, $content);
        fclose($stream);
    }

    public function download(string $remotePath): string
    {
        if ($this->connection === null) {
            throw new RuntimeException('Not connected. Call connect() first.');
        }

        $sftp = \ssh2_sftp($this->connection);

        if ($sftp === false) {
            throw new RuntimeException('Failed to initialize SFTP subsystem.');
        }

        $stream = fopen("ssh2.sftp://{$sftp}{$remotePath}", 'r');

        if ($stream === false) {
            throw new RuntimeException("Failed to open remote file: {$remotePath}");
        }

        $contents = stream_get_contents($stream);
        fclose($stream);

        if ($contents === false) {
            throw new RuntimeException("Failed to read remote file: {$remotePath}");
        }

        return $contents;
    }

    public function disconnect(): void
    {
        if ($this->connection !== null) {
            $this->connection = null;
        }
    }
}
