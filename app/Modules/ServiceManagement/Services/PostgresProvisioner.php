<?php

namespace App\Modules\ServiceManagement\Services;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\DTOs\ProvisionedDatabaseConnectionData;
use Illuminate\Support\Str;

class PostgresProvisioner implements DatabaseProvisioner
{
    public function __construct(
        private readonly ClusterRoleResolver $clusterRoleResolver = new ClusterRoleResolver,
    ) {}

    public function provision(Cluster $cluster, string $name, ?string $version = null): ProvisionedDatabaseConnectionData
    {
        $server = $this->clusterRoleResolver->resolveDbHost($cluster);
        $normalizedName = Str::snake($name);

        return new ProvisionedDatabaseConnectionData(
            host: $server->public_ip,
            port: 5432,
            databaseName: $normalizedName,
            username: Str::lower(Str::snake($name)).'_user',
            password: Str::random(32),
        );
    }

    public function rotateCredentials(string $databaseName): array
    {
        return [
            'username' => Str::lower(Str::snake($databaseName)).'_user',
            'password' => Str::random(32),
        ];
    }
}
