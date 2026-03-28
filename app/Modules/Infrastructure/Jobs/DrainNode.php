<?php

namespace App\Modules\Infrastructure\Jobs;

use App\Modules\Infrastructure\Actions\DrainNode as DrainNodeAction;
use App\Modules\Infrastructure\Models\Server;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DrainNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public Server $server) {}

    public function handle(): void
    {
        (new DrainNodeAction)->execute($this->server);
    }

    public function queue(): string
    {
        return QueueName::Infrastructure->value;
    }
}
