<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Events\DeploymentCompleted;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Enums\ArtifactStatus;
use Illuminate\Support\Facades\DB;

class ActivateRelease
{
    public function execute(Deployment $deployment): Release
    {
        return DB::transaction(function () use ($deployment) {
            $deployment->loadMissing('release.artifact', 'environment.activeRelease');

            $release = $deployment->release;
            $environment = $deployment->environment;
            $previousActiveRelease = $environment->activeRelease;

            $deployment->finished_at = now();
            $deployment->save();

            if ($deployment->status !== DeploymentStatus::Succeeded) {
                $deployment->transitionTo(DeploymentStatus::Succeeded);
            }

            if ($release->status !== ReleaseStatus::Active) {
                $release->transitionTo(ReleaseStatus::Active);
            }

            $environment->forceFill(['active_release_id' => $release->id])->save();

            if ($previousActiveRelease !== null && $previousActiveRelease->isNot($release)) {
                if ($previousActiveRelease->status !== ReleaseStatus::Superseded) {
                    $previousActiveRelease->transitionTo(ReleaseStatus::Superseded);
                }
            }

            if ($release->artifact->status === ArtifactStatus::Ready && $release->artifact->canTransitionTo(ArtifactStatus::Deployed)) {
                $release->artifact->transitionTo(ArtifactStatus::Deployed);
            }

            event(new DeploymentCompleted($deployment->fresh(['release', 'environment'])));

            return $release->fresh();
        });
    }
}
