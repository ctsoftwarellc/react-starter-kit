<?php

namespace Tests\Feature\Api\ServiceManagement;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceBindingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_binding_endpoints_require_authentication(): void
    {
        $environment = Environment::factory()->create();
        $binding = ServiceBinding::factory()->create(['environment_id' => $environment->id]);

        $this->getJson('/api/v1/environments/'.$environment->id.'/service-bindings')->assertUnauthorized();
        $this->postJson('/api/v1/environments/'.$environment->id.'/service-bindings', [])->assertUnauthorized();
        $this->deleteJson('/api/v1/environments/'.$environment->id.'/service-bindings/'.$binding->id)->assertUnauthorized();
    }

    public function test_can_list_service_bindings(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        ServiceBinding::factory()->create([
            'environment_id' => $environment->id,
            'binding_name' => 'Primary Database',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id.'/service-bindings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.binding_name', 'Primary Database')
            ->assertJsonMissingPath('data.0.database_instance.password');
    }

    public function test_can_bind_a_database_service_and_generate_environment_secrets(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $database = DatabaseInstance::factory()->create([
            'host' => '10.0.0.10',
            'port' => 5432,
            'database_name' => 'orders',
            'username' => 'orders_user',
            'password' => 'orders-password',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/service-bindings', [
                'service_type' => 'database',
                'database_instance_id' => $database->id,
                'binding_name' => 'Primary Database',
                'config' => ['ssl' => true],
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.service_type', 'database')
            ->assertJsonPath('data.binding_name', 'Primary Database')
            ->assertJsonMissingPath('data.database_instance.password');

        $this->assertDatabaseHas('service_bindings', [
            'environment_id' => $environment->id,
            'service_type' => ServiceType::Database->value,
            'database_instance_id' => $database->id,
            'binding_name' => 'Primary Database',
        ]);
        $this->assertDatabaseHas('secrets', ['environment_id' => $environment->id, 'key' => 'PRIMARY_DATABASE_PASSWORD']);
    }

    public function test_can_bind_cache_and_storage_services(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $cache = CacheInstance::factory()->create();
        $bucket = StorageBucket::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/service-bindings', [
                'service_type' => 'cache',
                'cache_instance_id' => $cache->id,
                'binding_name' => 'Primary Cache',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.service_type', 'cache');

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/service-bindings', [
                'service_type' => 'storage',
                'storage_bucket_id' => $bucket->id,
                'binding_name' => 'Asset Storage',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.service_type', 'storage');
    }

    public function test_binding_validation_requires_ids_for_the_selected_service_type(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/service-bindings', [
                'service_type' => 'database',
                'binding_name' => 'Primary Database',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['database_instance_id']);

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/service-bindings', [
                'binding_name' => 'Missing Type',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_type']);
    }

    public function test_can_unbind_a_service_and_remove_generated_secrets(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $binding = ServiceBinding::factory()->create([
            'environment_id' => $environment->id,
            'binding_name' => 'Primary Database',
        ]);
        foreach (['CONNECTION', 'HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD'] as $suffix) {
            $environment->secrets()->create([
                'key' => 'PRIMARY_DATABASE_'.$suffix,
                'encrypted_value' => 'value',
                'version' => 1,
            ]);
        }

        $this->actingAs($user)
            ->deleteJson('/api/v1/environments/'.$environment->id.'/service-bindings/'.$binding->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('service_bindings', ['id' => $binding->id]);
        $this->assertDatabaseMissing('secrets', ['environment_id' => $environment->id, 'key' => 'PRIMARY_DATABASE_PASSWORD']);
    }
}
