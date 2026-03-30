<?php

namespace App\Modules\Deployment\Services;

use App\Modules\Deployment\Actions\ActivateRelease;
use App\Modules\Deployment\Actions\DeployToNode;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Events\DeploymentFailed;
use App\Modules\Deployment\Jobs\RunPostDeployHealthCheck;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeploymentCoordinator
{
    public function execute(Deployment $deployment): Deployment
    {
        $deployment->loadMissing('release', 'environment.cluster');

        if ($deployment->status === DeploymentStatus::Cancelled) {
            return $deployment;
        }

        $deployment->started_at ??= now();
        $deployment->save();

        if ($deployment->canTransitionTo(DeploymentStatus::Preparing)) {
            $deployment->transitionTo(DeploymentStatus::Preparing);
        }

        $servers = $this->resolveTargetServers($deployment->environment);

        if ($servers->isEmpty()) {
            return $this->markDeploymentFailed($deployment, 'No eligible target servers were found.');
        }

        $steps = $this->createSteps($deployment, $servers);
        $deployment->refresh();

        if ($deployment->canTransitionTo(DeploymentStatus::Deploying)) {
            $deployment->transitionTo(DeploymentStatus::Deploying);
        }

        foreach ($steps as $step) {
            $step->forceFill([
                'status' => DeploymentStepStatus::Deploying,
                'started_at' => now(),
            ])->save();

            $command = (new DeployToNode)->execute($deployment, $step);
            $command = $this->waitForAgentCommandResult($command);

            if ($command->status !== AgentCommandStatus::Completed) {
                $step->forceFill([
                    'status' => DeploymentStepStatus::Failed,
                    'finished_at' => now(),
                    'output' => $command->result['message'] ?? null,
                ])->save();

                return $this->markDeploymentFailed($deployment, $command->result['message'] ?? 'Deployment command failed.');
            }

            $step->forceFill([
                'status' => DeploymentStepStatus::Deployed,
                'output' => $command->result['message'] ?? null,
            ])->save();

            dispatch_sync(new RunPostDeployHealthCheck($deployment->fresh(), $step->fresh()));

            if ($step->fresh()->status === DeploymentStepStatus::Deployed) {
                (new RunPostDeployHealthCheck($deployment->fresh(), $step->fresh()))->handle();
            }

            if ($step->fresh()->status !== DeploymentStepStatus::Active) {
                return $this->markDeploymentFailed($deployment, 'Health check failed after deployment.');
            }

            $deployment->increment('completed_nodes');
        }

        $deployment->refresh();

        if ($deployment->canTransitionTo(DeploymentStatus::Verifying)) {
            $deployment->transitionTo(DeploymentStatus::Verifying);
        }

        (new ActivateRelease)->execute($deployment->fresh());

        return $deployment->fresh(['release', 'environment', 'steps']);
    }

    public function resolveTargetServers($environment): Collection
    {
        $environment->loadMissing('cluster');

        return Server::query()
            ->select('servers.*')
            ->join('cluster_node', 'cluster_node.server_id', '=', 'servers.id')
            ->where('cluster_node.cluster_id', $environment->cluster_id)
            ->where('cluster_node.role', 'web')
            ->where('cluster_node.is_active', true)
            ->where('servers.status', ServerStatus::Active)
            ->orderBy('cluster_node.sort_order')
            ->get();
    }

    public function createSteps(Deployment $deployment, Collection $servers): Collection
    {
        return DB::transaction(function () use ($deployment, $servers) {
            $deployment->forceFill([
                'total_nodes' => $servers->count(),
                'completed_nodes' => 0,
                'failed_nodes' => 0,
            ])->save();

            $deployment->steps()->delete();

            $steps = $servers->map(fn (Server $server) => new DeploymentStep([
                'server_id' => $server->id,
                'status' => DeploymentStepStatus::Pending,
            ]));

            $deployment->steps()->saveMany($steps->all());

            return $deployment->steps()->with('server')->ordered()->get();
        });
    }

    public function waitForAgentCommandResult(AgentCommand $command, int $timeoutSeconds = 300): AgentCommand
    {
        $deadline = now()->addSeconds($timeoutSeconds);

        do {
            $command = $command->fresh();

            if (in_array($command->status, [AgentCommandStatus::Completed, AgentCommandStatus::Failed, AgentCommandStatus::Expired], true)) {
                return $command;
            }

            sleep(1);
        } while (now()->lt($deadline));

        $command->forceFill([
            'status' => AgentCommandStatus::Expired,
            'result' => array_merge($command->result ?? [], ['message' => 'Timed out waiting for agent command result.']),
            'completed_at' => now(),
        ])->save();

        return $command->fresh();
    }

    private function markDeploymentFailed(Deployment $deployment, ?string $message = null): Deployment
    {
        $deployment->refresh();
        $deployment->failed_nodes = max(1, $deployment->failed_nodes + 1);
        $deployment->finished_at = now();
        $deployment->save();

        if (in_array($deployment->status, [DeploymentStatus::Preparing, DeploymentStatus::Deploying, DeploymentStatus::Verifying], true)) {
            $deployment->transitionTo(DeploymentStatus::Failed);
        }

        $release = $deployment->release()->first();

        if ($release !== null && $release->status !== ReleaseStatus::Failed) {
            if ($release->canTransitionTo(ReleaseStatus::Failed)) {
                $release->transitionTo(ReleaseStatus::Failed);
            }
        }

        event(new DeploymentFailed($deployment->fresh(['release', 'environment'])));

        if ($message !== null) {
            throw new RuntimeException($message);
        }

        return $deployment->fresh();
    }
}
