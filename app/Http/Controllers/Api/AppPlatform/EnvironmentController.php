<?php

namespace App\Http\Controllers\Api\AppPlatform;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppPlatform\CreateEnvironmentRequest;
use App\Http\Requests\AppPlatform\UpdateEnvironmentRequest;
use App\Http\Resources\AppPlatform\EnvironmentResource;
use App\Modules\AppPlatform\Actions\CreateEnvironment;
use App\Modules\AppPlatform\Actions\DeleteEnvironment;
use App\Modules\AppPlatform\Actions\UpdateEnvironment;
use App\Modules\AppPlatform\DTOs\CreateEnvironmentData;
use App\Modules\AppPlatform\DTOs\UpdateEnvironmentData;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EnvironmentController extends Controller
{
    public function index(Application $application): AnonymousResourceCollection
    {
        return EnvironmentResource::collection(
            $application->environments()->with('cluster')->latest()->paginate(15),
        );
    }

    public function store(CreateEnvironmentRequest $request, Application $application): EnvironmentResource
    {
        $environment = (new CreateEnvironment)->execute(
            $application,
            CreateEnvironmentData::from($request->validated()),
        );

        return new EnvironmentResource($environment->load(['application', 'cluster']));
    }

    public function show(Environment $environment): EnvironmentResource
    {
        return new EnvironmentResource(
            $environment->load(['application', 'cluster', 'variables', 'secrets', 'processDefinitions', 'runtimeProfile', 'serverRoleProfiles', 'remoteCommands.server']),
        );
    }

    public function update(UpdateEnvironmentRequest $request, Environment $environment): EnvironmentResource
    {
        $environment = (new UpdateEnvironment)->execute(
            $environment,
            UpdateEnvironmentData::from($request->validated()),
        );

        return new EnvironmentResource($environment->load(['application', 'cluster']));
    }

    public function destroy(Environment $environment): Response
    {
        (new DeleteEnvironment)->execute($environment);

        return response()->noContent();
    }
}
