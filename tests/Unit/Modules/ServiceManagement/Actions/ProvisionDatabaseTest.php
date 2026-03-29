<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\Actions\ProvisionDatabase;
use App\Modules\ServiceManagement\DTOs\ProvisionDatabaseData;
use App\Modules\ServiceManagement\Enums\DatabaseEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\InteractsWithServiceManagement;
use Tests\TestCase;

class ProvisionDatabaseTest extends TestCase
{
    use InteractsWithServiceManagement;
    use RefreshDatabase;

    public function test_it_provisions_a_database_instance_for_a_cluster_with_a_db_node(): void
    {
        $cluster = Cluster::factory()->create();
        $server = $this->attachClusterNode($cluster, NodeRole::Db);

        $database = (new ProvisionDatabase)->execute(new ProvisionDatabaseData(
            clusterId: $cluster->id,
            name: 'Primary Database',
            engine: DatabaseEngine::Postgres,
            version: '16',
        ));

        $this->assertDatabaseHas('database_instances', [
            'id' => $database->id,
            'cluster_id' => $cluster->id,
            'name' => 'Primary Database',
            'engine' => DatabaseEngine::Postgres->value,
            'version' => '16',
            'host' => $server->public_ip,
            'port' => 5432,
            'database_name' => 'primary_database',
            'username' => 'primary_database_user',
        ]);
    }

    public function test_it_requires_a_cluster_db_node(): void
    {
        $cluster = Cluster::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cluster [{$cluster->name}] has no db node available.");

        (new ProvisionDatabase)->execute(new ProvisionDatabaseData(
            clusterId: $cluster->id,
            name: 'Primary Database',
            engine: DatabaseEngine::Mysql,
        ));
    }
}
