<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\DTOs\ProvisionCacheData;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Services\RedisProvisioner;
use Illuminate\Support\Facades\DB;

class ProvisionCache
{
    public function execute(ProvisionCacheData $data): CacheInstance
    {
        $cluster = Cluster::findOrFail($data->clusterId);
        $connection = (new RedisProvisioner)->provision($cluster, $data->name, $data->version);

        return DB::transaction(function () use ($cluster, $data, $connection) {
            return CacheInstance::create([
                'cluster_id' => $cluster->id,
                'name' => $data->name,
                'engine' => $data->engine,
                'version' => $data->version,
                'host' => $connection->host,
                'port' => $connection->port,
                'password' => $connection->password,
            ]);
        });
    }
}
