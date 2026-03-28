<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\AddNodeToCluster;
use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AddNodeToClusterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_attaches_server_to_cluster_with_role(): void
    {
        Event::fake();

        $cluster = Cluster::factory()->create();
        $server = Server::factory()->create();

        (new AddNodeToCluster)->execute($cluster, $server, 'web');

        $this->assertDatabaseHas('cluster_node', [
            'cluster_id' => $cluster->id,
            'server_id' => $server->id,
            'role' => 'web',
            'is_active' => true,
        ]);
    }

    public function test_it_dispatches_cluster_topology_changed_event(): void
    {
        Event::fake();

        $cluster = Cluster::factory()->create();
        $server = Server::factory()->create();

        (new AddNodeToCluster)->execute($cluster, $server, 'worker');

        Event::assertDispatched(ClusterTopologyChanged::class, function (ClusterTopologyChanged $event) use ($cluster) {
            return $event->cluster->id === $cluster->id
                && $event->changeType === 'node_added';
        });
    }
}
