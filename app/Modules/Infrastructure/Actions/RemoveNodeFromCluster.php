<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Facades\DB;

class RemoveNodeFromCluster
{
    public function execute(Cluster $cluster, Server $server): Cluster
    {
        return DB::transaction(function () use ($cluster, $server) {
            $cluster->servers()->detach($server->id);

            event(new ClusterTopologyChanged($cluster, 'node_removed'));

            return $cluster;
        });
    }
}
