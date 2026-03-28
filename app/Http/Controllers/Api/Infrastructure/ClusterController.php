<?php

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\AddNodeRequest;
use App\Http\Requests\Infrastructure\CreateClusterRequest;
use App\Http\Requests\Infrastructure\UpdateClusterRequest;
use App\Http\Requests\Infrastructure\UpdateNodeRequest;
use App\Http\Resources\Infrastructure\ClusterResource;
use App\Modules\Infrastructure\Actions\AddNodeToCluster;
use App\Modules\Infrastructure\Actions\CreateCluster;
use App\Modules\Infrastructure\Actions\DeleteCluster;
use App\Modules\Infrastructure\Actions\RemoveNodeFromCluster;
use App\Modules\Infrastructure\Actions\UpdateCluster;
use App\Modules\Infrastructure\Actions\UpdateNodeRole;
use App\Modules\Infrastructure\DTOs\CreateClusterData;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClusterController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $clusters = Cluster::with('servers')->latest()->paginate(15);

        return ClusterResource::collection($clusters);
    }

    public function store(CreateClusterRequest $request): ClusterResource
    {
        $cluster = (new CreateCluster)->execute(
            CreateClusterData::from($request->validated()),
        );

        return new ClusterResource($cluster);
    }

    public function show(Cluster $cluster): ClusterResource
    {
        $cluster->load('servers');

        return new ClusterResource($cluster);
    }

    public function update(UpdateClusterRequest $request, Cluster $cluster): ClusterResource
    {
        $cluster = (new UpdateCluster)->execute($cluster, $request->validated());

        return new ClusterResource($cluster);
    }

    public function destroy(Cluster $cluster): Response
    {
        (new DeleteCluster)->execute($cluster);

        return response()->noContent();
    }

    public function addNode(AddNodeRequest $request, Cluster $cluster): ClusterResource
    {
        $server = Server::findOrFail($request->validated('server_id'));

        $cluster = (new AddNodeToCluster)->execute(
            $cluster,
            $server,
            $request->validated('role'),
        );

        $cluster->load('servers');

        return new ClusterResource($cluster);
    }

    public function removeNode(Cluster $cluster, Server $server): ClusterResource
    {
        $cluster = (new RemoveNodeFromCluster)->execute($cluster, $server);

        $cluster->load('servers');

        return new ClusterResource($cluster);
    }

    public function updateNode(UpdateNodeRequest $request, Cluster $cluster, Server $server): ClusterResource
    {
        $cluster = (new UpdateNodeRole)->execute(
            $cluster,
            $server,
            $request->validated('role'),
        );

        $cluster->load('servers');

        return new ClusterResource($cluster);
    }
}
