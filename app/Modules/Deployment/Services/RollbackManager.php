<?php

namespace App\Modules\Deployment\Services;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\RollbackNodeDeployment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Events\RollbackCompleted;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Deployment\Models\Release;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Models\AgentCommand;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RollbackManager
{
    public function execute(Deployment $rollbackDeployment, ?Deployment $failedDeployment = null): Deployment
    {
        $rollbackDeployment->loadMissing('release', 'environment.activeRelease');

        $rollbackDeployment->started_at ??= now();
        $rollbackDeployment->save();

        if ($rollbackDeployment->canTransitionTo(DeploymentStatus::Preparing)) {
            $rollbackDeployment->transitionTo(DeploymentStatus::Preparing);
        }

        $steps = $this->prepareSteps($rollbackDeployment, $failedDeployment);

        if ($rollbackDeployment->canTransitionTo(DeploymentStatus::Deploying)) {
            $rollbackDeployment->transitionTo(DeploymentStatus::Deploying);
        }

        foreach ($steps as $step) {
            $step->forceFill([
                'status' => DeploymentStepStatus::Deploying,
                'started_at' => now(),
            ])->save();

            $command = $this->rollbackServer($step, $rollbackDeployment->release);
            $command = (new DeploymentCoordinator)->waitForAgentCommandResult($command);

            if ($command->status !== AgentCommandStatus::Completed) {
                $step->forceFill([
                    'status' => DeploymentStepStatus::Failed,
                    'finished_at' => now(),
                    'output' => $command->result['message'] ?? null,
                ])->save();

                $this->markRollbackFailed($rollbackDeployment, $failedDeployment, $command->result['message'] ?? 'Rollback failed.');

                throw new RuntimeException($command->result['message'] ?? 'Rollback failed.');
            }

            $step->forceFill([
                'status' => DeploymentStepStatus::RolledBack,
                'finished_at' => now(),
                'output' => $command->result['message'] ?? null,
            ])->save();

            $rollbackDeployment->increment('completed_nodes');
        }

        return DB::transaction(function () use ($rollbackDeployment, $failedDeployment) {
            $environment = $rollbackDeployment->environment()->first();
            $rollbackRelease = $rollbackDeployment->release()->first();

            $rollbackDeployment->finished_at = now();
            $rollbackDeployment->save();

            if ($rollbackDeployment->canTransitionTo(DeploymentStatus::Verifying)) {
                $rollbackDeployment->transitionTo(DeploymentStatus::Verifying);
            }

            if ($rollbackDeployment->canTransitionTo(DeploymentStatus::Succeeded)) {
                $rollbackDeployment->transitionTo(DeploymentStatus::Succeeded);
            }

            if ($environment !== null && $rollbackRelease !== null) {
                $environment->forceFill(['active_release_id' => $rollbackRelease->id])->save();

                if ($rollbackRelease->status !== ReleaseStatus::Active && $rollbackRelease->canTransitionTo(ReleaseStatus::Active)) {
                    $rollbackRelease->transitionTo(ReleaseStatus::Active);
                }
            }

            if ($failedDeployment !== null) {
                $failedDeployment->refresh();
                $failedDeployment->finished_at ??= now();
                $failedDeployment->save();

                if ($failedDeployment->status === DeploymentStatus::Failed && $failedDeployment->canTransitionTo(DeploymentStatus::RolledBack)) {
                    $failedDeployment->transitionTo(DeploymentStatus::RolledBack);
                }

                $failedRelease = $failedDeployment->release()->first();

                if ($failedRelease !== null) {
                    if ($failedRelease->status === ReleaseStatus::Failed && $failedRelease->canTransitionTo(ReleaseStatus::RolledBack)) {
                        $failedRelease->transitionTo(ReleaseStatus::RolledBack);
                    }
                }
            }

            event(new RollbackCompleted($rollbackDeployment->fresh(['release', 'environment'])));

            return $rollbackDeployment->fresh(['release', 'environment', 'steps']);
        });
    }

    public function resolveRollbackRelease(Environment $environment, Release $failedRelease): ?Release
    {
        $environment->loadMissing('activeRelease');

        if ($environment->activeRelease !== null && $environment->activeRelease->isNot($failedRelease)) {
            return $environment->activeRelease;
        }

        return $environment->releases()
            ->whereKeyNot($failedRelease->id)
            ->latest('version')
            ->first();
    }

    public function rollbackServer(DeploymentStep $step, Release $rollbackRelease): AgentCommand
    {
        $deployment = $step->deployment()->firstOrFail();
        $deployment->setRelation('release', $rollbackRelease);

        return (new RollbackNodeDeployment)->execute($deployment, $step);
    }

    public function markRollbackFailed(Deployment $rollbackDeployment, ?Deployment $failedDeployment = null, ?string $message = null): Deployment
    {
        return DB::transaction(function () use ($rollbackDeployment, $failedDeployment, $message) {
            $rollbackDeployment->refresh();
            $rollbackDeployment->failed_nodes = max(1, $rollbackDeployment->failed_nodes + 1);
            $rollbackDeployment->finished_at = now();
            $rollbackDeployment->save();

            if (in_array($rollbackDeployment->status, [DeploymentStatus::Preparing, DeploymentStatus::Deploying, DeploymentStatus::Verifying], true)
                && $rollbackDeployment->canTransitionTo(DeploymentStatus::Failed)) {
                $rollbackDeployment->transitionTo(DeploymentStatus::Failed);
            }

            $rollbackRelease = $rollbackDeployment->release()->first();

            if ($rollbackRelease !== null && $rollbackRelease->status === ReleaseStatus::Deploying && $rollbackRelease->canTransitionTo(ReleaseStatus::Failed)) {
                $rollbackRelease->transitionTo(ReleaseStatus::Failed);
            }

            if ($failedDeployment !== null) {
                $failedDeployment->refresh();

                if ($failedDeployment->finished_at === null) {
                    $failedDeployment->finished_at = now();
                    $failedDeployment->save();
                }

                $failedRelease = $failedDeployment->release()->first();

                if ($failedRelease !== null && $failedRelease->status === ReleaseStatus::Deploying && $failedRelease->canTransitionTo(ReleaseStatus::Failed)) {
                    $failedRelease->transitionTo(ReleaseStatus::Failed);
                }
            }

            if ($message !== null) {
                $rollbackDeployment->steps()
                    ->where('status', DeploymentStepStatus::Deploying)
                    ->update([
                        'status' => DeploymentStepStatus::Failed,
                        'finished_at' => now(),
                        'output' => $message,
                    ]);
            }

            return $rollbackDeployment->fresh(['release', 'environment', 'steps']);
        });
    }

    private function prepareSteps(Deployment $rollbackDeployment, ?Deployment $failedDeployment): Collection
    {
        return DB::transaction(function () use ($rollbackDeployment, $failedDeployment) {
            $servers = $failedDeployment?->steps()
                ->whereIn('deployment_steps.status', [
                    DeploymentStepStatus::Deployed,
                    DeploymentStepStatus::HealthChecking,
                    DeploymentStepStatus::Active,
                ])
                ->with('server')
                ->ordered()
                ->get()
                ->pluck('server')
                ->filter();

            if ($servers === null || $servers->isEmpty()) {
                $servers = (new DeploymentCoordinator)->resolveTargetServers($rollbackDeployment->environment()->firstOrFail());
            }

            $rollbackDeployment->forceFill([
                'total_nodes' => $servers->count(),
                'completed_nodes' => 0,
                'failed_nodes' => 0,
            ])->save();

            $rollbackDeployment->steps()->delete();

            $steps = $servers->map(fn ($server) => new DeploymentStep([
                'server_id' => $server->id,
                'status' => DeploymentStepStatus::Pending,
            ]));

            $rollbackDeployment->steps()->saveMany($steps->all());

            return $rollbackDeployment->steps()->with('server')->ordered()->get();
        });
    }
}
