<?php

namespace App\Modules\ServiceManagement\Services;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\DTOs\ProvisionedDatabaseConnectionData;

interface DatabaseProvisioner
{
    public function provision(Cluster $cluster, string $name, ?string $version = null): ProvisionedDatabaseConnectionData;

    public function rotateCredentials(string $databaseName): array;
}
