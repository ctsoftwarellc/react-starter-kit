<?php

namespace Tests\Unit\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Actions\AddNodeToCluster;
use App\Modules\Infrastructure\Actions\RemoveNodeFromCluster;
use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RemoveNodeFromClusterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detaches_server_from_cluster(): void
    {
        Event::fake();

        $cluster = Cluster::factory()->create();
        $server = Server::factory()->create();

        (new AddNodeToCluster)->execute($cluster, $server, 'web');
        $this->assertDatabaseHas('cluster_node', [
            'cluster_id' => $cluster->id,
            'server_id' => $server->id,
        ]);

        (new RemoveNodeFromCluster)->execute($cluster, $server);

        $this->assertDatabaseMissing('cluster_node', [
            'cluster_id' => $cluster->id,
            'server_id' => $server->id,
        ]);
    }

    public function test_it_dispatches_cluster_topology_changed_event(): void
    {
        Event::fake();

        $cluster = Cluster::factory()->create();
        $server = Server::factory()->create();

        (new AddNodeToCluster)->execute($cluster, $server, 'web');
        (new RemoveNodeFromCluster)->execute($cluster, $server);

        Event::assertDispatched(ClusterTopologyChanged::class, function (ClusterTopologyChanged $event) use ($cluster) {
            return $event->cluster->id === $cluster->id
                && $event->changeType === 'node_removed';
        });
    }
}
