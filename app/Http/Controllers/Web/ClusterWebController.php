<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Infrastructure\AddNodeRequest;
use App\Http\Requests\Infrastructure\CreateClusterRequest;
use App\Modules\Infrastructure\Actions\AddNodeToCluster;
use App\Modules\Infrastructure\Actions\CreateCluster;
use App\Modules\Infrastructure\Actions\RemoveNodeFromCluster;
use App\Modules\Infrastructure\DTOs\CreateClusterData;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClusterWebController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('clusters/index', [
            'clusters' => Cluster::with('servers')->latest()->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('clusters/create');
    }

    public function store(CreateClusterRequest $request): RedirectResponse
    {
        $cluster = (new CreateCluster)->execute(
            CreateClusterData::from($request->validated()),
        );

        return redirect()->route('clusters.show', $cluster);
    }

    public function show(Cluster $cluster): Response
    {
        $cluster->load('servers');

        $availableServers = Server::whereDoesntHave('clusters', function ($query) use ($cluster) {
            $query->where('clusters.id', $cluster->id);
        })->get();

        return Inertia::render('clusters/show', [
            'cluster' => $cluster,
            'availableServers' => $availableServers,
        ]);
    }

    public function addNode(AddNodeRequest $request, Cluster $cluster): RedirectResponse
    {
        $server = Server::findOrFail($request->validated('server_id'));

        (new AddNodeToCluster)->execute(
            $cluster,
            $server,
            $request->validated('role'),
        );

        return back()->with('success', 'Node added to cluster.');
    }

    public function removeNode(Cluster $cluster, Server $server): RedirectResponse
    {
        (new RemoveNodeFromCluster)->execute($cluster, $server);

        return back()->with('success', 'Node removed from cluster.');
    }
}
