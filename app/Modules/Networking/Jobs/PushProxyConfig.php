<?php

namespace App\Modules\Networking\Jobs;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Networking\Actions\PushProxyConfig as PushProxyConfigAction;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PushProxyConfig implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public Cluster $cluster) {}

    public function handle(): void
    {
        (new PushProxyConfigAction)->execute($this->cluster);
    }

    public function queue(): string
    {
        return QueueName::Infrastructure->value;
    }
}
