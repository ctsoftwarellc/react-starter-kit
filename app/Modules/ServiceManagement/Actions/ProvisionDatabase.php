<?php

namespace App\Modules\ServiceManagement\Actions;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\DTOs\ProvisionDatabaseData;
use App\Modules\ServiceManagement\Enums\DatabaseEngine;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Services\DatabaseProvisioner;
use App\Modules\ServiceManagement\Services\MysqlProvisioner;
use App\Modules\ServiceManagement\Services\PostgresProvisioner;
use Illuminate\Support\Facades\DB;

class ProvisionDatabase
{
    public function execute(ProvisionDatabaseData $data): DatabaseInstance
    {
        $cluster = Cluster::findOrFail($data->clusterId);
        $provisioner = $this->resolveProvisioner($data->engine);
        $connection = $provisioner->provision($cluster, $data->name, $data->version);

        return DB::transaction(function () use ($cluster, $data, $connection) {
            return DatabaseInstance::create([
                'cluster_id' => $cluster->id,
                'name' => $data->name,
                'engine' => $data->engine,
                'version' => $data->version,
                'host' => $connection->host,
                'port' => $connection->port,
                'database_name' => $connection->databaseName,
                'username' => $connection->username,
                'password' => $connection->password,
            ]);
        });
    }

    private function resolveProvisioner(DatabaseEngine $engine): DatabaseProvisioner
    {
        return match ($engine) {
            DatabaseEngine::Postgres => new PostgresProvisioner,
            DatabaseEngine::Mysql => new MysqlProvisioner,
        };
    }
}
