<?php

namespace Tests\Feature\Api\ServiceManagement;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageBucketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_bucket_endpoints_require_authentication(): void
    {
        $bucket = StorageBucket::factory()->create();

        $this->getJson('/api/v1/storage-buckets')->assertUnauthorized();
        $this->postJson('/api/v1/storage-buckets', [])->assertUnauthorized();
        $this->getJson('/api/v1/storage-buckets/'.$bucket->id)->assertUnauthorized();
        $this->deleteJson('/api/v1/storage-buckets/'.$bucket->id)->assertUnauthorized();
        $this->postJson('/api/v1/storage-buckets/'.$bucket->id.'/rotate-credentials')->assertUnauthorized();
    }

    public function test_can_list_and_show_storage_buckets_without_leaking_credentials(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $bucket = StorageBucket::factory()->create([
            'access_key' => 'TOPSECRETACCESSKEY',
            'secret_key' => 'top-secret-key',
        ]);

        $listResponse = $this->actingAs($user)->getJson('/api/v1/storage-buckets');
        $showResponse = $this->actingAs($user)->getJson('/api/v1/storage-buckets/'.$bucket->id);

        $listResponse->assertOk()
            ->assertJsonMissingPath('data.0.access_key')
            ->assertJsonMissingPath('data.0.secret_key');
        $showResponse->assertOk()
            ->assertJsonMissingPath('data.access_key')
            ->assertJsonMissingPath('data.secret_key');

        $this->assertStringNotContainsString('TOPSECRETACCESSKEY', $listResponse->getContent());
        $this->assertStringNotContainsString('top-secret-key', $showResponse->getContent());
    }

    public function test_can_provision_a_storage_bucket(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/storage-buckets', [
            'name' => 'Assets',
            'provider' => 's3',
            'region' => 'us-east-1',
            'bucket_name' => 'assets-bucket',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'Assets')
            ->assertJsonPath('data.bucket_name', 'assets-bucket')
            ->assertJsonMissingPath('data.access_key')
            ->assertJsonMissingPath('data.secret_key');
    }

    public function test_provisioning_a_storage_bucket_requires_all_fields(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/storage-buckets', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'provider', 'region', 'bucket_name']);
    }

    public function test_cannot_delete_a_bound_storage_bucket(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $bucket = StorageBucket::factory()->create();
        ServiceBinding::factory()->create([
            'service_type' => ServiceType::Storage,
            'database_instance_id' => null,
            'storage_bucket_id' => $bucket->id,
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/storage-buckets/'.$bucket->id)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot delete a storage bucket while it is bound to an environment.');
    }

    public function test_can_delete_an_unbound_storage_bucket(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $bucket = StorageBucket::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/storage-buckets/'.$bucket->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('storage_buckets', ['id' => $bucket->id]);
    }

    public function test_rotating_credentials_updates_bound_environment_secrets_without_leaking_credentials(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $bucket = StorageBucket::factory()->create([
            'access_key' => 'OLDACCESSKEY',
            'secret_key' => 'old-secret-key',
        ]);
        ServiceBinding::factory()->create([
            'environment_id' => $environment->id,
            'service_type' => ServiceType::Storage,
            'database_instance_id' => null,
            'storage_bucket_id' => $bucket->id,
            'binding_name' => 'Asset Storage',
        ]);
        Secret::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'ASSET_STORAGE_SECRET_ACCESS_KEY',
            'encrypted_value' => 'old-secret-key',
            'version' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/storage-buckets/'.$bucket->id.'/rotate-credentials');

        $response->assertOk()
            ->assertJsonMissingPath('data.access_key')
            ->assertJsonMissingPath('data.secret_key');

        $this->assertStringNotContainsString('OLDACCESSKEY', $response->getContent());
        $this->assertStringNotContainsString('old-secret-key', $response->getContent());
        $this->assertSame(2, $environment->secrets()->where('key', 'ASSET_STORAGE_SECRET_ACCESS_KEY')->firstOrFail()->version);
        $this->assertSame($bucket->fresh()->secret_key, $environment->secrets()->where('key', 'ASSET_STORAGE_SECRET_ACCESS_KEY')->firstOrFail()->encrypted_value);
    }
}
