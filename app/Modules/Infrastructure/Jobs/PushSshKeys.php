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

        $ssh->execute('mkdir -p ~/.ssh && chmod 700 ~/.ssh');
        $ssh->upload('~/.ssh/authorized_keys', $authorizedKeys);
        $ssh->execute('chmod 600 ~/.ssh/authorized_keys');

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
