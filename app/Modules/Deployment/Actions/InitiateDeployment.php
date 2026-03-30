<?php

namespace App\Modules\Deployment\Actions;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStrategy;
use App\Modules\Deployment\Events\DeploymentStarted;
use App\Modules\Deployment\Jobs\ExecuteDeployment;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Models\Artifact;
use App\Support\Enums\QueueName;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InitiateDeployment
{
    public function execute(
        Environment $environment,
        Artifact|Release $target,
        DeploymentStrategy $strategy = DeploymentStrategy::Rolling,
        ?User $initiatedBy = null,
    ): Deployment {
        if ($strategy !== DeploymentStrategy::Rolling) {
            throw new RuntimeException('Only rolling deployments are supported in MVP.');
        }

        $deployment = DB::transaction(function () use ($environment, $target, $strategy, $initiatedBy) {
            $release = $target instanceof Artifact
                ? (new CreateRelease)->execute($environment, $target, $initiatedBy)
                : $target;

            if ($release->environment_id !== $environment->id) {
                throw new RuntimeException('Release does not belong to the selected environment.');
            }

            if ($release->status !== 'deploying' && $release->canTransitionTo('deploying')) {
                $release->transitionTo('deploying');
            }

            $deployment = Deployment::create([
                'release_id' => $release->id,
                'environment_id' => $environment->id,
                'status' => DeploymentStatus::Pending,
                'strategy' => $strategy,
                'total_nodes' => 0,
                'completed_nodes' => 0,
                'failed_nodes' => 0,
                'initiated_by' => $initiatedBy?->id,
            ]);

            event(new DeploymentStarted($deployment));

            DB::afterCommit(fn () => ExecuteDeployment::dispatch($deployment)->onQueue(QueueName::Deployment->value));

            return $deployment;
        });

        return $deployment->fresh(['release', 'environment']);
    }
}
