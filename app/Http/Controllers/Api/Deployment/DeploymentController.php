<?php

namespace App\Http\Controllers\Api\Deployment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deployment\InitiateDeploymentRequest;
use App\Http\Requests\Deployment\RollbackDeploymentRequest;
use App\Http\Resources\Deployment\DeploymentResource;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\CancelDeployment;
use App\Modules\Deployment\Actions\InitiateDeployment;
use App\Modules\Deployment\Actions\RollbackDeployment;
use App\Modules\Deployment\Enums\DeploymentStrategy;
use App\Modules\Deployment\Models\Deployment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeploymentController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return DeploymentResource::collection(
            $environment->deployments()
                ->with(['release.artifact.pipelineRun.pipeline', 'steps.server'])
                ->latestFirst()
                ->paginate(15),
        );
    }

    public function store(InitiateDeploymentRequest $request, Environment $environment): JsonResponse
    {
        $target = $request->validated('artifact_id')
            ? $environment->application->artifacts()->whereKey($request->validated('artifact_id'))->firstOrFail()
            : $environment->releases()->whereKey($request->validated('release_id'))->firstOrFail();

        $deployment = (new InitiateDeployment)->execute(
            $environment,
            $target,
            DeploymentStrategy::from($request->validated('strategy')),
            $request->user(),
        );

        return (new DeploymentResource($deployment->load(['release.artifact.pipelineRun.pipeline', 'environment', 'steps.server'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Deployment $deployment): DeploymentResource
    {
        return new DeploymentResource(
            $deployment->load(['release.artifact.pipelineRun.pipeline.application', 'environment.application', 'steps.server']),
        );
    }

    public function cancel(Deployment $deployment): DeploymentResource
    {
        $deployment = (new CancelDeployment)->execute($deployment);

        return new DeploymentResource($deployment->load(['release.artifact.pipelineRun.pipeline', 'environment', 'steps.server']));
    }

    public function rollback(RollbackDeploymentRequest $request, Environment $environment): JsonResponse
    {
        $release = $environment->releases()->whereKey($request->validated('release_id'))->firstOrFail();

        $deployment = (new RollbackDeployment)->execute($environment, $release, $request->user());

        return (new DeploymentResource($deployment->load(['release.artifact.pipelineRun.pipeline', 'environment', 'steps.server'])))
            ->response()
            ->setStatusCode(201);
    }
}
