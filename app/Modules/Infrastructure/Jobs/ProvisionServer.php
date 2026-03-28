<?php

namespace App\Modules\Infrastructure\Jobs;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Infrastructure\Services\Providers\ProviderFactory;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProvisionServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public Server $server) {}

    public function handle(): void
    {
        $this->server->transitionTo(ServerStatus::Provisioning);

        $provider = ProviderFactory::make($this->server->provider);

        $result = $provider->createServer([
            'name' => $this->server->name,
            'region' => $this->server->region,
        ]);

        $this->server->update([
            'public_ip' => $result['public_ip'] ?? $this->server->public_ip,
            'private_ip' => $result['private_ip'] ?? $this->server->private_ip,
            'os' => $result['os'] ?? $this->server->os,
            'cpu_cores' => $result['cpu_cores'] ?? $this->server->cpu_cores,
            'memory_mb' => $result['memory_mb'] ?? $this->server->memory_mb,
            'disk_gb' => $result['disk_gb'] ?? $this->server->disk_gb,
        ]);

        $this->server->transitionTo(ServerStatus::Bootstrapping);
    }

    public function failed(Throwable $e): void
    {
        $this->server->transitionTo(ServerStatus::Failed);
    }

    public function queue(): string
    {
        return QueueName::Infrastructure->value;
    }
}
