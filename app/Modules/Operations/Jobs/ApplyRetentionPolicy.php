<?php

namespace App\Modules\Operations\Jobs;

use App\Modules\Operations\Actions\ApplyBackupRetentionPolicy;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyRetentionPolicy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function handle(): void
    {
        (new ApplyBackupRetentionPolicy)->execute();
    }

    public function queue(): string
    {
        return QueueName::Maintenance->value;
    }
}
