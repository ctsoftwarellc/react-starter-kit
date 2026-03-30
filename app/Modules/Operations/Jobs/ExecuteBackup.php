<?php

namespace App\Modules\Operations\Jobs;

use App\Modules\Operations\Actions\ExecuteBackup as ExecuteBackupAction;
use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Models\Backup;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExecuteBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public Backup $backup) {}

    public function handle(): void
    {
        (new ExecuteBackupAction)->execute($this->backup);
    }

    public function failed(Throwable $exception): void
    {
        if ($this->backup->status !== BackupStatus::Failed) {
            $this->backup->transitionTo(BackupStatus::Failed);
        }

        $this->backup->update(['finished_at' => now()]);
    }

    public function queue(): string
    {
        return QueueName::Maintenance->value;
    }
}
