<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\Actions\ProvisionCache;
use App\Modules\ServiceManagement\DTOs\ProvisionCacheData;
use App\Modules\ServiceManagement\Enums\CacheEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\InteractsWithServiceManagement;
use Tests\TestCase;

class ProvisionCacheTest extends TestCase
{
    use InteractsWithServiceManagement;
    use RefreshDatabase;

    public function test_it_provisions_a_cache_instance_for_a_cluster_with_a_cache_node(): void
    {
        $cluster = Cluster::factory()->create();
        $server = $this->attachClusterNode($cluster, NodeRole::Cache);

        $cache = (new ProvisionCache)->execute(new ProvisionCacheData(
            clusterId: $cluster->id,
            name: 'Primary Cache',
            engine: CacheEngine::Redis,
            version: '7',
        ));

        $this->assertDatabaseHas('cache_instances', [
            'id' => $cache->id,
            'cluster_id' => $cluster->id,
            'name' => 'Primary Cache',
            'engine' => CacheEngine::Redis->value,
            'version' => '7',
            'host' => $server->public_ip,
            'port' => 6379,
        ]);
    }

    public function test_it_requires_a_cluster_cache_node(): void
    {
        $cluster = Cluster::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cluster [{$cluster->name}] has no cache node available.");

        (new ProvisionCache)->execute(new ProvisionCacheData(
            clusterId: $cluster->id,
            name: 'Primary Cache',
            engine: CacheEngine::Valkey,
        ));
    }
}
