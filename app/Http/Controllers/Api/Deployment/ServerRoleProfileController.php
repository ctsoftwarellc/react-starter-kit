<?php

namespace App\Http\Controllers\Api\Deployment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deployment\ServerRoleProfileRequest;
use App\Http\Resources\Deployment\ServerRoleProfileResource;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\SaveServerRoleProfile;
use App\Modules\Deployment\Models\ServerRoleProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ServerRoleProfileController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return ServerRoleProfileResource::collection($environment->serverRoleProfiles()->latest()->get());
    }

    public function store(ServerRoleProfileRequest $request, Environment $environment): JsonResponse
    {
        $profile = (new SaveServerRoleProfile)->execute($environment, $request->validated());

        return (new ServerRoleProfileResource($profile))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ServerRoleProfileRequest $request, ServerRoleProfile $serverRoleProfile): ServerRoleProfileResource
    {
        $profile = (new SaveServerRoleProfile)->execute($serverRoleProfile->environment, $request->validated(), $serverRoleProfile);

        return new ServerRoleProfileResource($profile);
    }
}
