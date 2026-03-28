<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Facades\DB;

class UpdateNodeRole
{
    public function execute(Cluster $cluster, Server $server, string $role): Cluster
    {
        return DB::transaction(function () use ($cluster, $server, $role) {
            $cluster->servers()->updateExistingPivot($server->id, [
                'role' => $role,
            ]);

            event(new ClusterTopologyChanged($cluster, 'node_role_updated'));

            return $cluster;
        });
    }
}
