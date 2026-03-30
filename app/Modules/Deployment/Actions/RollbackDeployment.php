<?php

namespace App\Modules\Deployment\Actions;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStrategy;
use App\Modules\Deployment\Jobs\ExecuteRollback;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\Release;
use App\Support\Enums\QueueName;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RollbackDeployment
{
    public function execute(Deployment|Environment $subject, ?Release $rollbackRelease = null, ?User $initiatedBy = null): Deployment
    {
        if ($subject instanceof Environment) {
            return $this->executeManualRollback($subject, $rollbackRelease, $initiatedBy);
        }

        $rollbackDeployment = DB::transaction(function () use ($subject, $rollbackRelease, $initiatedBy) {
            $failedDeployment = $subject instanceof Deployment ? $subject : null;
            $environment = $subject instanceof Deployment ? $subject->environment()->firstOrFail() : $subject;

            $environment->loadMissing('activeRelease');
            $failedDeployment?->loadMissing('release', 'environment.activeRelease');

            $targetRelease = $rollbackRelease
                ?? $environment->activeRelease;

            if ($failedDeployment !== null && ($targetRelease === null || $targetRelease->id === $failedDeployment->release_id)) {
                throw new RuntimeException('No previous active release is available for rollback.');
            }

            if ($targetRelease === null) {
                throw new RuntimeException('A rollback release must be selected.');
            }

            if ($targetRelease->environment_id !== $environment->id) {
                throw new RuntimeException('Rollback release does not belong to the selected environment.');
            }

            if ($failedDeployment === null && $environment->active_release_id === $targetRelease->id) {
                throw new RuntimeException('The selected release is already active.');
            }

            $rollbackDeployment = $environment->deployments()->create([
                'release_id' => $targetRelease->id,
                'status' => DeploymentStatus::Pending,
                'strategy' => DeploymentStrategy::Rolling,
                'total_nodes' => 0,
                'completed_nodes' => 0,
                'failed_nodes' => 0,
                'initiated_by' => $initiatedBy?->id ?? $failedDeployment?->initiated_by,
            ]);

            DB::afterCommit(fn () => ExecuteRollback::dispatch($rollbackDeployment, $failedDeployment)->onQueue(QueueName::Deployment->value));

            return $rollbackDeployment;
        });

        return $rollbackDeployment->fresh(['release', 'environment']);
    }

    private function executeManualRollback(Environment $environment, ?Release $rollbackRelease, ?User $initiatedBy): Deployment
    {
        if ($rollbackRelease === null) {
            throw new RuntimeException('A rollback release must be selected.');
        }

        if ($rollbackRelease->environment_id !== $environment->id) {
            throw new RuntimeException('Rollback release does not belong to the selected environment.');
        }

        if ($environment->active_release_id === $rollbackRelease->id) {
            throw new RuntimeException('The selected release is already active.');
        }

        $clonedRelease = (new CloneReleaseForRollback)->execute($rollbackRelease, $initiatedBy);

        return (new InitiateDeployment)->execute(
            $environment,
            $clonedRelease,
            DeploymentStrategy::Rolling,
            $initiatedBy,
        );
    }
}
