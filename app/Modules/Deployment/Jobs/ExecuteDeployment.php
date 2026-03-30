<?php

namespace App\Modules\Deployment\Jobs;

use App\Modules\Deployment\Actions\ExecuteDeploymentWorkflow;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Models\Deployment;
use App\Support\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExecuteDeployment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public Deployment $deployment) {}

    public function handle(): void
    {
        (new ExecuteDeploymentWorkflow)->execute($this->deployment->fresh());
    }

    public function failed(Throwable $exception): void
    {
        report($exception);

        $deployment = $this->deployment->fresh('release');

        if (in_array($deployment->status, [DeploymentStatus::Pending, DeploymentStatus::Preparing, DeploymentStatus::Deploying, DeploymentStatus::Verifying], true)) {
            $deployment->finished_at = now();
            $deployment->save();

            if ($deployment->status !== DeploymentStatus::Pending) {
                $deployment->transitionTo(DeploymentStatus::Failed);
            }
        }

        if ($deployment->release !== null && in_array($deployment->release->status, [ReleaseStatus::Pending, ReleaseStatus::Deploying], true)) {
            if ($deployment->release->status !== ReleaseStatus::Pending && $deployment->release->canTransitionTo(ReleaseStatus::Failed)) {
                $deployment->release->transitionTo(ReleaseStatus::Failed);
            }
        }
    }

    public function queue(): string
    {
        return QueueName::Deployment->value;
    }
}
