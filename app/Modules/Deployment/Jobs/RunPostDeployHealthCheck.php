<?php

namespace App\Modules\Deployment\Jobs;

use App\Modules\Deployment\Actions\RunHealthCheck;
use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunPostDeployHealthCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public Deployment $deployment, public DeploymentStep $step) {}

    public function handle(): bool
    {
        return (new RunHealthCheck)->execute($this->deployment->fresh(), $this->step->fresh());
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        $this->step->forceFill([
            'status' => DeploymentStepStatus::Failed,
            'finished_at' => now(),
        ])->save();

        $this->deployment->increment('failed_nodes');
    }

    public function queue(): string
    {
        return QueueName::Deployment->value;
    }
}
