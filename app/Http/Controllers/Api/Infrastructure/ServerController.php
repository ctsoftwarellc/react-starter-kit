<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\RegisterServerRequest;
use App\Http\Requests\Infrastructure\UpdateServerRequest;
use App\Http\Resources\Infrastructure\ServerResource;
use App\Modules\Infrastructure\Actions\ActivateNode;
use App\Modules\Infrastructure\Actions\CordonNode;
use App\Modules\Infrastructure\Actions\DeleteServer;
use App\Modules\Infrastructure\Actions\DrainNode;
use App\Modules\Infrastructure\Actions\RegisterServer;
use App\Modules\Infrastructure\Actions\UpdateServer;
use App\Modules\Infrastructure\DTOs\RegisterServerData;
use App\Modules\Infrastructure\DTOs\UpdateServerData;
use App\Modules\Infrastructure\Jobs\BootstrapServer;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Server::with(['provider', 'clusters'])->latest();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return ServerResource::collection($query->paginate(15));
    }

    public function store(RegisterServerRequest $request): ServerResource
    {
        $server = (new RegisterServer)->execute(
            RegisterServerData::from($request->validated()),
        );

        return new ServerResource($server);
    }

    public function show(Server $server): ServerResource
    {
        $server->load(['provider', 'clusters']);

        return new ServerResource($server);
    }

    public function update(UpdateServerRequest $request, Server $server): ServerResource
    {
        $server = (new UpdateServer)->execute(
            $server,
            UpdateServerData::from($request->validated()),
        );

        return new ServerResource($server);
    }

    public function destroy(Server $server): Response
    {
        (new DeleteServer)->execute($server);

        return response()->noContent();
    }

    public function bootstrap(Server $server): JsonResponse
    {
        BootstrapServer::dispatch($server);

        return response()->json(['message' => 'Bootstrap job dispatched.'], 202);
    }

    public function drain(Server $server): ServerResource
    {
        $server = (new DrainNode)->execute($server);

        return new ServerResource($server);
    }

    public function cordon(Server $server): ServerResource
    {
        $server = (new CordonNode)->execute($server);

        return new ServerResource($server);
    }

    public function activate(Server $server): ServerResource
    {
        $server = (new ActivateNode)->execute($server);

        return new ServerResource($server);
    }
}
