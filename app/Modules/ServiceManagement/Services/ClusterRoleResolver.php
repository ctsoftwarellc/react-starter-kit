<?php

namespace App\Modules\ServiceManagement\Services;

use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use InvalidArgumentException;

class ClusterRoleResolver
{
    public function resolveDbHost(Cluster $cluster): Server
    {
        return $this->resolveByRole($cluster, NodeRole::Db);
    }

    public function resolveCacheHost(Cluster $cluster): Server
    {
        return $this->resolveByRole($cluster, NodeRole::Cache);
    }

    private function resolveByRole(Cluster $cluster, NodeRole $role): Server
    {
        $server = $cluster->servers()->wherePivot('role', $role->value)->first();

        if ($server === null) {
            throw new InvalidArgumentException("Cluster [{$cluster->name}] has no {$role->value} node available.");
        }

        return $server;
    }
}
