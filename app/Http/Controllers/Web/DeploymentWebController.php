<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Deployment\DeploymentResource;
use App\Http\Resources\Deployment\DeploymentStepResource;
use App\Http\Resources\Deployment\HealthCheckResource;
use App\Http\Resources\Deployment\ReleaseResource;
use App\Modules\Deployment\Models\Deployment;
use Inertia\Inertia;
use Inertia\Response;

class DeploymentWebController extends Controller
{
    public function show(Deployment $deployment): Response
    {
        $deployment->load([
            'release.artifact.pipelineRun.pipeline',
            'environment.application.project',
            'steps' => fn ($query) => $query->with('server')->ordered(),
        ]);

        $healthCheck = $deployment->environment->healthChecks()->latest('created_at')->first();
        $availableRollbackReleases = $deployment->environment->releases()
            ->with(['artifact.pipelineRun.pipeline'])
            ->where('id', '!=', $deployment->release_id)
            ->latestVersionFirst()
            ->limit(10)
            ->get();

        return Inertia::render('deployments/show', [
            'deployment' => (new DeploymentResource($deployment))->resolve(),
            'release' => (new ReleaseResource($deployment->release))->resolve(),
            'environment' => $deployment->environment,
            'application' => $deployment->environment->application,
            'steps' => DeploymentStepResource::collection($deployment->steps)->resolve(),
            'healthCheck' => $healthCheck ? (new HealthCheckResource($healthCheck))->resolve() : null,
            'availableRollbackReleases' => ReleaseResource::collection($availableRollbackReleases)->resolve(),
        ]);
    }
}
