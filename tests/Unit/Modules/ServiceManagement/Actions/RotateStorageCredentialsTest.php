<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\ServiceManagement\Actions\RotateStorageCredentials;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotateStorageCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rotates_storage_credentials(): void
    {
        $bucket = StorageBucket::factory()->create([
            'access_key' => 'OLDACCESSKEY',
            'secret_key' => 'old-secret-key',
        ]);

        $rotated = (new RotateStorageCredentials)->execute($bucket);

        $this->assertNotSame('OLDACCESSKEY', $rotated->access_key);
        $this->assertNotSame('old-secret-key', $rotated->secret_key);
    }

    public function test_it_updates_generated_environment_secrets_for_bound_environments(): void
    {
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
            'key' => 'ASSET_STORAGE_ACCESS_KEY_ID',
            'encrypted_value' => 'OLDACCESSKEY',
            'version' => 1,
        ]);
        Secret::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'ASSET_STORAGE_SECRET_ACCESS_KEY',
            'encrypted_value' => 'old-secret-key',
            'version' => 1,
        ]);

        (new RotateStorageCredentials)->execute($bucket);

        $bucket->refresh();

        $this->assertDatabaseHas('secrets', [
            'environment_id' => $environment->id,
            'key' => 'ASSET_STORAGE_ACCESS_KEY_ID',
            'version' => 2,
        ]);
        $this->assertDatabaseHas('secrets', [
            'environment_id' => $environment->id,
            'key' => 'ASSET_STORAGE_SECRET_ACCESS_KEY',
            'version' => 2,
        ]);
        $this->assertSame($bucket->access_key, $environment->secrets()->where('key', 'ASSET_STORAGE_ACCESS_KEY_ID')->firstOrFail()->encrypted_value);
        $this->assertSame($bucket->secret_key, $environment->secrets()->where('key', 'ASSET_STORAGE_SECRET_ACCESS_KEY')->firstOrFail()->encrypted_value);
    }
}
