<?php

namespace App\Modules\Infrastructure\Jobs;

use App\Modules\Infrastructure\Actions\BootstrapServer as BootstrapServerAction;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Server;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class BootstrapServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public Server $server) {}

    public function handle(): void
    {
        (new BootstrapServerAction)->execute($this->server);
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
