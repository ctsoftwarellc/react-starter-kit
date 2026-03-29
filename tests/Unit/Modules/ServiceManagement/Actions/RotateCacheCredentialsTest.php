<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\ServiceManagement\Actions\RotateCacheCredentials;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotateCacheCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rotates_cache_credentials(): void
    {
        $cache = CacheInstance::factory()->create(['password' => 'old-password']);

        $rotated = (new RotateCacheCredentials)->execute($cache);

        $this->assertNotSame('old-password', $rotated->password);
    }

    public function test_it_updates_generated_environment_secrets_for_bound_environments(): void
    {
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

        (new RotateCacheCredentials)->execute($cache);

        $cache->refresh();

        $this->assertDatabaseHas('secrets', [
            'environment_id' => $environment->id,
            'key' => 'PRIMARY_CACHE_PASSWORD',
            'version' => 2,
        ]);
        $this->assertSame($cache->password, $environment->secrets()->where('key', 'PRIMARY_CACHE_PASSWORD')->firstOrFail()->encrypted_value);
    }
}
