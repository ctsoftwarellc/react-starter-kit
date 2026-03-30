<?php

namespace App\Http\Controllers\Api\Deployment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deployment\ExecuteRemoteCommandRequest;
use App\Http\Resources\Deployment\RemoteCommandResource;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\ExecuteRemoteCommand;
use App\Modules\Deployment\Models\RemoteCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;

class RemoteCommandController extends Controller
{
    public function index(Environment $environment): AnonymousResourceCollection
    {
        return RemoteCommandResource::collection($environment->remoteCommands()->with('server')->latest()->paginate(15));
    }

    public function store(ExecuteRemoteCommandRequest $request, Environment $environment): JsonResponse
    {
        try {
            $remoteCommand = (new ExecuteRemoteCommand)->execute($environment, $request->validated());
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        return (new RemoteCommandResource($remoteCommand->load('server')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(RemoteCommand $remoteCommand): RemoteCommandResource
    {
        return new RemoteCommandResource($remoteCommand->load(['server', 'environment']));
    }
}
