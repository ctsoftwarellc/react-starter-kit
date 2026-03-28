<?php

namespace App\Modules\Infrastructure\Jobs;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Server;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DecommissionNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public Server $server) {}

    public function handle(): void
    {
        $this->server->clusters()->detach();

        $this->server->transitionTo(ServerStatus::Decommissioned);
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
