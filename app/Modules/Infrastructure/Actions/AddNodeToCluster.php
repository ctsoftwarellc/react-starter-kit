<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AddNodeToCluster
{
    public function execute(Cluster $cluster, Server $server, string $role): Cluster
    {
        return DB::transaction(function () use ($cluster, $server, $role) {
            $cluster->servers()->attach($server->id, [
                'id' => Str::ulid()->toString(),
                'role' => $role,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            event(new ClusterTopologyChanged($cluster, 'node_added'));

            return $cluster;
        });
    }
}
