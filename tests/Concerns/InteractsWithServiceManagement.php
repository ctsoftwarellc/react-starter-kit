<?php

namespace Tests\Concerns;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Str;

trait InteractsWithServiceManagement
{
    protected function attachClusterNode(Cluster $cluster, NodeRole $role, ?Server $server = null): Server
    {
        $server ??= Server::factory()->create();

        $cluster->servers()->attach($server->id, [
            'id' => Str::ulid()->toString(),
            'role' => $role->value,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $server;
    }

    protected function createEnvironmentSecretsMap(Environment $environment): array
    {
        return $environment->secrets()
            ->get()
            ->mapWithKeys(fn ($secret) => [$secret->key => $secret->encrypted_value])
            ->all();
    }
}
