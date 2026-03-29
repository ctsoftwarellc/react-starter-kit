<?php

namespace Tests\Feature\Api\ServiceManagement;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithServiceManagement;
use Tests\TestCase;

class CacheInstanceControllerTest extends TestCase
{
    use InteractsWithServiceManagement;
    use RefreshDatabase;

    public function test_cache_instance_endpoints_require_authentication(): void
    {
        $cache = CacheInstance::factory()->create();

        $this->getJson('/api/v1/cache-instances')->assertUnauthorized();
        $this->postJson('/api/v1/cache-instances', [])->assertUnauthorized();
        $this->getJson('/api/v1/cache-instances/'.$cache->id)->assertUnauthorized();
        $this->deleteJson('/api/v1/cache-instances/'.$cache->id)->assertUnauthorized();
        $this->postJson('/api/v1/cache-instances/'.$cache->id.'/rotate-credentials')->assertUnauthorized();
    }

    public function test_can_list_and_show_cache_instances_without_leaking_passwords(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cache = CacheInstance::factory()->create(['password' => 'top-secret-password']);

        $listResponse = $this->actingAs($user)->getJson('/api/v1/cache-instances');
        $showResponse = $this->actingAs($user)->getJson('/api/v1/cache-instances/'.$cache->id);

        $listResponse->assertOk()->assertJsonMissingPath('data.0.password');
        $showResponse->assertOk()->assertJsonMissingPath('data.password');

        $this->assertStringNotContainsString('top-secret-password', $listResponse->getContent());
        $this->assertStringNotContainsString('top-secret-password', $showResponse->getContent());
    }

    public function test_can_provision_a_cache_instance(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cluster = Cluster::factory()->create();
        $server = $this->attachClusterNode($cluster, NodeRole::Cache);

        $response = $this->actingAs($user)->postJson('/api/v1/cache-instances', [
            'cluster_id' => $cluster->id,
            'name' => 'Primary Cache',
            'engine' => 'redis',
            'version' => '7',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.cluster_id', $cluster->id)
            ->assertJsonPath('data.host', $server->public_ip)
            ->assertJsonMissingPath('data.password');
    }

    public function test_provisioning_a_cache_requires_cluster_id_name_and_engine(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/cache-instances', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cluster_id', 'name', 'engine']);
    }

    public function test_provisioning_a_cache_requires_a_cache_node_in_the_cluster(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cluster = Cluster::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/cache-instances', [
                'cluster_id' => $cluster->id,
                'name' => 'Primary Cache',
                'engine' => 'redis',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', "Cluster [{$cluster->name}] has no cache node available.");
    }

    public function test_cannot_delete_a_bound_cache_instance(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cache = CacheInstance::factory()->create();
        ServiceBinding::factory()->create([
            'service_type' => ServiceType::Cache,
            'database_instance_id' => null,
            'cache_instance_id' => $cache->id,
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/cache-instances/'.$cache->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot delete a cache instance while it is bound to an environment.');
    }

    public function test_can_delete_an_unbound_cache_instance(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cache = CacheInstance::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/cache-instances/'.$cache->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('cache_instances', ['id' => $cache->id]);
    }

    public function test_rotating_credentials_updates_bound_environment_secrets_without_leaking_passwords(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $cache = CacheInstance::factory()->create(['password' => 'old-password']);
        ServiceBinding::factory()->create([
            'environment_id' => $environment->id,
            'service_type' => ServiceType::Cache,
            'database_instance_id' => null,
            'cache_instance_id' => $cache->id,
            'binding_name' => 'Primary Cache',
        ]);
        Secret::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'PRIMARY_CACHE_PASSWORD',
            'encrypted_value' => 'old-password',
            'version' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/cache-instances/'.$cache->id.'/rotate-credentials');

        $response->assertOk()->assertJsonMissingPath('data.password');

        $this->assertStringNotContainsString('old-password', $response->getContent());
        $this->assertSame(2, $environment->secrets()->where('key', 'PRIMARY_CACHE_PASSWORD')->firstOrFail()->version);
        $this->assertSame($cache->fresh()->password, $environment->secrets()->where('key', 'PRIMARY_CACHE_PASSWORD')->firstOrFail()->encrypted_value);
    }
}
