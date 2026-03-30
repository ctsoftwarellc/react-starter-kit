<?php

namespace App\Modules\Deployment\Listeners;

use App\Modules\Deployment\Events\DeploymentCompleted;
use App\Modules\Pipeline\Enums\ArtifactStatus;

class CleanupOldReleases
{
    public function handle(DeploymentCompleted $event): void
    {
        $releases = $event->deployment->environment()
            ->firstOrFail()
            ->releases()
            ->latest('version')
            ->skip(5)
            ->take(PHP_INT_MAX)
            ->with('artifact')
            ->get();

        foreach ($releases as $release) {
            if ($release->artifact !== null && $release->artifact->status === ArtifactStatus::Deployed && $release->artifact->canTransitionTo(ArtifactStatus::Superseded)) {
                $release->artifact->transitionTo(ArtifactStatus::Superseded);
            }
        }
    }
}
