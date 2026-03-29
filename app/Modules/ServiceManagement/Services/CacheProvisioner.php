<?php

namespace App\Modules\ServiceManagement\Services;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\DTOs\ProvisionedCacheConnectionData;

interface CacheProvisioner
{
    public function provision(Cluster $cluster, string $name, ?string $version = null): ProvisionedCacheConnectionData;

    public function rotateCredentials(): ?string;
}
