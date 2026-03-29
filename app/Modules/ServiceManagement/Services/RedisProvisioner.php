<?php

namespace App\Modules\ServiceManagement\Services;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\DTOs\ProvisionedCacheConnectionData;
use Illuminate\Support\Str;

class RedisProvisioner implements CacheProvisioner
{
    public function __construct(
        private readonly ClusterRoleResolver $clusterRoleResolver = new ClusterRoleResolver,
    ) {}

    public function provision(Cluster $cluster, string $name, ?string $version = null): ProvisionedCacheConnectionData
    {
        $server = $this->clusterRoleResolver->resolveCacheHost($cluster);

        return new ProvisionedCacheConnectionData(
            host: $server->public_ip,
            port: 6379,
            password: Str::random(32),
        );
    }

    public function rotateCredentials(): ?string
    {
        return Str::random(32);
    }
}
