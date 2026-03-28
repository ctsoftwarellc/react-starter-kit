<?php

namespace Tests\Feature\Api\Infrastructure;

use App\Actions\CreatePersonalAccessToken;
use App\Models\User;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClusterControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(): array
    {
        $user = User::factory()->create();
        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        return [$user, $result->plainTextToken];
    }

    public function test_can_list_clusters(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        Cluster::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/clusters');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_cluster(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/clusters', [
                'name' => 'Production Cluster',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Production Cluster');
        $response->assertJsonPath('data.slug', 'production-cluster');
        $response->assertJsonPath('data.status', 'pending');
    }

    public function test_can_show_cluster(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $cluster = Cluster::factory()->create([
            'name' => 'Test Cluster',
            'slug' => 'test-cluster',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/clusters/'.$cluster->slug);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Test Cluster');
    }

    public function test_can_update_cluster(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $cluster = Cluster::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/clusters/'.$cluster->slug, [
                'name' => 'Updated Name',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_cluster(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $cluster = Cluster::factory()->create(['slug' => 'to-delete']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/clusters/'.$cluster->slug);

        $response->assertNoContent();
        $this->assertDatabaseMissing('clusters', ['id' => $cluster->id]);
    }

    public function test_can_add_node_to_cluster(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $cluster = Cluster::factory()->create(['slug' => 'test-cluster']);
        $server = Server::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/clusters/'.$cluster->slug.'/nodes', [
                'server_id' => $server->id,
                'role' => 'web',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('cluster_node', [
            'cluster_id' => $cluster->id,
            'server_id' => $server->id,
            'role' => 'web',
        ]);
    }

    public function test_can_remove_node_from_cluster(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $cluster = Cluster::factory()->create(['slug' => 'test-cluster']);
        $server = Server::factory()->create();

        $cluster->servers()->attach($server->id, [
            'id' => Str::ulid()->toString(),
            'role' => 'web',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/clusters/'.$cluster->slug.'/nodes/'.$server->id);

        $response->assertOk();
        $this->assertDatabaseMissing('cluster_node', [
            'cluster_id' => $cluster->id,
            'server_id' => $server->id,
        ]);
    }

    public function test_can_update_node_role(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $cluster = Cluster::factory()->create(['slug' => 'test-cluster']);
        $server = Server::factory()->create();

        $cluster->servers()->attach($server->id, [
            'id' => Str::ulid()->toString(),
            'role' => 'web',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/clusters/'.$cluster->slug.'/nodes/'.$server->id, [
                'role' => 'worker',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('cluster_node', [
            'cluster_id' => $cluster->id,
            'server_id' => $server->id,
            'role' => 'worker',
        ]);
    }

    public function test_create_validation_fails_missing_required_fields(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/clusters', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/clusters');

        $response->assertUnauthorized();
    }

    public function test_add_node_validation_fails(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $cluster = Cluster::factory()->create(['slug' => 'test-cluster']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/clusters/'.$cluster->slug.'/nodes', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['server_id', 'role']);
    }
}
