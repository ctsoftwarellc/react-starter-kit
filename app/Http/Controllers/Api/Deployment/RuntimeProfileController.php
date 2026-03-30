<?php

namespace App\Http\Controllers\Api\Deployment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deployment\ApplyRuntimeProfileRequest;
use App\Http\Requests\Deployment\RuntimeProfileRequest;
use App\Http\Resources\Deployment\RuntimeProfileResource;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\ApplyRuntimeProfile;
use App\Modules\Deployment\Actions\CreateRuntimeProfile;
use App\Modules\Deployment\Actions\UpdateRuntimeProfile;
use App\Modules\Deployment\Models\RuntimeProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RuntimeProfileController extends Controller
{
    public function index(Application $application): AnonymousResourceCollection
    {
        return RuntimeProfileResource::collection($application->runtimeProfiles()->latest()->get());
    }

    public function store(RuntimeProfileRequest $request, Application $application): JsonResponse
    {
        return (new RuntimeProfileResource((new CreateRuntimeProfile)->execute($application, $request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(RuntimeProfileRequest $request, RuntimeProfile $runtimeProfile): RuntimeProfileResource
    {
        return new RuntimeProfileResource((new UpdateRuntimeProfile)->execute($runtimeProfile, $request->validated()));
    }

    public function apply(ApplyRuntimeProfileRequest $request, Environment $environment): RuntimeProfileResource
    {
        $runtimeProfile = $environment->application->runtimeProfiles()->whereKey($request->validated('runtime_profile_id'))->firstOrFail();

        (new ApplyRuntimeProfile)->execute($environment, $runtimeProfile);

        return new RuntimeProfileResource($runtimeProfile->fresh());
    }
}
