<?php

namespace App\Modules\Infrastructure\Jobs;

use App\Models\SshKey;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Infrastructure\Services\SshService;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PushSshKeys implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public Server $server) {}

    public function handle(): void
    {
        $keys = SshKey::all();

        if ($keys->isEmpty()) {
            return;
        }

        $authorizedKeys = $keys->pluck('public_key')->implode("\n");

        $ssh = new SshService;
        $ssh->connect(
            $this->server->public_ip,
            $this->server->ssh_port,
            $this->server->ssh_user,
        );

        $ssh->execute('mkdir -p /root/.ssh && chmod 700 /root/.ssh');
        $ssh->upload('/root/.ssh/authorized_keys', $authorizedKeys);
        $ssh->execute('chmod 600 /root/.ssh/authorized_keys');
        $ssh->execute('if id -u helm >/dev/null 2>&1; then mkdir -p /home/helm/.ssh && chmod 700 /home/helm/.ssh; fi');
        $ssh->upload('/tmp/helm_authorized_keys', $authorizedKeys);
        $ssh->execute('if id -u helm >/dev/null 2>&1; then mv /tmp/helm_authorized_keys /home/helm/.ssh/authorized_keys && chown helm:helm /home/helm/.ssh/authorized_keys && chmod 600 /home/helm/.ssh/authorized_keys; else rm -f /tmp/helm_authorized_keys; fi');

        $ssh->disconnect();
    }

    public function failed(Throwable $e): void
    {
        // SSH key push is non-critical — log but don't change server state
    }

    public function queue(): string
    {
        return QueueName::Infrastructure->value;
    }
}
