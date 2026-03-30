<?php

namespace App\Modules\Deployment\Jobs;

use App\Modules\Deployment\Actions\ExecuteRollbackWorkflow;
use App\Modules\Deployment\Actions\FailRollbackWorkflow;
use App\Modules\Deployment\Models\Deployment;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExecuteRollback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public Deployment $deployment, public ?Deployment $failedDeployment = null) {}

    public function handle(): void
    {
        (new ExecuteRollbackWorkflow)->execute($this->deployment->fresh(), $this->failedDeployment?->fresh());
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        (new FailRollbackWorkflow)->execute(
            $this->deployment->fresh(['release', 'environment.activeRelease']),
            $this->failedDeployment?->fresh(['release', 'environment.activeRelease']),
            $exception->getMessage(),
        );
    }

    public function queue(): string
    {
        return QueueName::Deployment->value;
    }
}
