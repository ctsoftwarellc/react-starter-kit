<?php

namespace Tests\Feature\Api\ServiceManagement;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithServiceManagement;
use Tests\TestCase;

class DatabaseInstanceControllerTest extends TestCase
{
    use InteractsWithServiceManagement;
    use RefreshDatabase;

    public function test_database_instance_endpoints_require_authentication(): void
    {
        $database = DatabaseInstance::factory()->create();

        $this->getJson('/api/v1/database-instances')->assertUnauthorized();
        $this->postJson('/api/v1/database-instances', [])->assertUnauthorized();
        $this->getJson('/api/v1/database-instances/'.$database->id)->assertUnauthorized();
        $this->deleteJson('/api/v1/database-instances/'.$database->id)->assertUnauthorized();
        $this->postJson('/api/v1/database-instances/'.$database->id.'/rotate-credentials')->assertUnauthorized();
    }

    public function test_can_list_and_show_database_instances_without_leaking_passwords(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $database = DatabaseInstance::factory()->create(['password' => 'top-secret-password']);

        $listResponse = $this->actingAs($user)->getJson('/api/v1/database-instances');
        $showResponse = $this->actingAs($user)->getJson('/api/v1/database-instances/'.$database->id);

        $listResponse->assertOk()
            ->assertJsonPath('data.0.id', $database->id)
            ->assertJsonMissingPath('data.0.password');
        $showResponse->assertOk()
            ->assertJsonPath('data.id', $database->id)
            ->assertJsonMissingPath('data.password');

        $this->assertStringNotContainsString('top-secret-password', $listResponse->getContent());
        $this->assertStringNotContainsString('top-secret-password', $showResponse->getContent());
    }

    public function test_can_provision_a_database_instance(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cluster = Cluster::factory()->create();
        $server = $this->attachClusterNode($cluster, NodeRole::Db);

        $response = $this->actingAs($user)->postJson('/api/v1/database-instances', [
            'cluster_id' => $cluster->id,
            'name' => 'Primary Database',
            'engine' => 'postgres',
            'version' => '16',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.cluster_id', $cluster->id)
            ->assertJsonPath('data.host', $server->public_ip)
            ->assertJsonPath('data.database_name', 'primary_database')
            ->assertJsonMissingPath('data.password');
    }

    public function test_provisioning_a_database_requires_cluster_id_name_and_engine(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/database-instances', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cluster_id', 'name', 'engine']);
    }

    public function test_provisioning_a_database_requires_a_db_node_in_the_cluster(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cluster = Cluster::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/database-instances', [
                'cluster_id' => $cluster->id,
                'name' => 'Primary Database',
                'engine' => 'postgres',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', "Cluster [{$cluster->name}] has no db node available.");
    }

    public function test_cannot_delete_a_bound_database_instance(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $database = DatabaseInstance::factory()->create();
        ServiceBinding::factory()->create(['database_instance_id' => $database->id]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/database-instances/'.$database->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot delete a database instance while it is bound to an environment.');
    }

    public function test_can_delete_an_unbound_database_instance(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $database = DatabaseInstance::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/database-instances/'.$database->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('database_instances', ['id' => $database->id]);
    }

    public function test_rotating_credentials_updates_bound_environment_secrets_without_leaking_passwords(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $database = DatabaseInstance::factory()->create([
            'database_name' => 'orders',
            'username' => 'old_user',
            'password' => 'old-password',
        ]);
        ServiceBinding::factory()->create([
            'environment_id' => $environment->id,
            'service_type' => ServiceType::Database,
            'database_instance_id' => $database->id,
            'binding_name' => 'Primary Database',
        ]);
        Secret::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'PRIMARY_DATABASE_PASSWORD',
            'encrypted_value' => 'old-password',
            'version' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/database-instances/'.$database->id.'/rotate-credentials');

        $response->assertOk()
            ->assertJsonPath('data.username', 'orders_user')
            ->assertJsonMissingPath('data.password');

        $this->assertStringNotContainsString('old-password', $response->getContent());
        $this->assertSame(2, $environment->secrets()->where('key', 'PRIMARY_DATABASE_PASSWORD')->firstOrFail()->version);
        $this->assertSame($database->fresh()->password, $environment->secrets()->where('key', 'PRIMARY_DATABASE_PASSWORD')->firstOrFail()->encrypted_value);
    }
}
