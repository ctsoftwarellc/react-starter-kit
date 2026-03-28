<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\CreateCluster;
use App\Modules\Infrastructure\DTOs\CreateClusterData;
use App\Modules\Infrastructure\Enums\ClusterStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateClusterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_cluster_with_generated_slug(): void
    {
        $data = new CreateClusterData(name: 'Production Cluster');

        $cluster = (new CreateCluster)->execute($data);

        $this->assertDatabaseHas('clusters', [
            'id' => $cluster->id,
            'name' => 'Production Cluster',
            'slug' => 'production-cluster',
        ]);
    }

    public function test_it_creates_cluster_in_pending_status(): void
    {
        $data = new CreateClusterData(name: 'Test Cluster');

        $cluster = (new CreateCluster)->execute($data);

        $this->assertEquals(ClusterStatus::Pending, $cluster->status);
    }

    public function test_it_handles_duplicate_slug_by_appending_suffix(): void
    {
        $first = (new CreateCluster)->execute(new CreateClusterData(name: 'My Cluster'));
        $second = (new CreateCluster)->execute(new CreateClusterData(name: 'My Cluster'));

        $this->assertEquals('my-cluster', $first->slug);
        $this->assertEquals('my-cluster-2', $second->slug);
    }

    public function test_it_stores_settings(): void
    {
        $data = new CreateClusterData(
            name: 'Configured Cluster',
            settings: ['max_nodes' => 10],
        );

        $cluster = (new CreateCluster)->execute($data);

        $this->assertEquals(['max_nodes' => 10], $cluster->settings);
    }
}
