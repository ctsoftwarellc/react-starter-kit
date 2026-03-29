<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\ServiceManagement\Actions\RotateDatabaseCredentials;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotateDatabaseCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rotates_database_credentials(): void
    {
        $database = DatabaseInstance::factory()->create([
            'database_name' => 'orders',
            'username' => 'old_user',
            'password' => 'old-password',
        ]);

        $rotated = (new RotateDatabaseCredentials)->execute($database);

        $this->assertSame('orders_user', $rotated->username);
        $this->assertNotSame('old-password', $rotated->password);
    }

    public function test_it_updates_generated_environment_secrets_for_bound_environments(): void
    {
        $environment = Environment::factory()->create();
        $database = DatabaseInstance::factory()->create([
            'name' => 'Orders Db',
            'database_name' => 'orders',
            'username' => 'old_user',
            'password' => 'old-password',
        ]);
        $binding = ServiceBinding::factory()->create([
            'environment_id' => $environment->id,
            'service_type' => ServiceType::Database,
            'database_instance_id' => $database->id,
            'binding_name' => 'Primary Database',
        ]);

        Secret::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'PRIMARY_DATABASE_USERNAME',
            'encrypted_value' => 'old_user',
            'version' => 1,
        ]);
        Secret::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'PRIMARY_DATABASE_PASSWORD',
            'encrypted_value' => 'old-password',
            'version' => 1,
        ]);

        (new RotateDatabaseCredentials)->execute($database);

        $binding->refresh();
        $database->refresh();

        $this->assertDatabaseHas('secrets', [
            'environment_id' => $environment->id,
            'key' => 'PRIMARY_DATABASE_USERNAME',
            'version' => 2,
        ]);
        $this->assertDatabaseHas('secrets', [
            'environment_id' => $environment->id,
            'key' => 'PRIMARY_DATABASE_PASSWORD',
            'version' => 2,
        ]);

        $this->assertSame($database->username, $environment->secrets()->where('key', 'PRIMARY_DATABASE_USERNAME')->firstOrFail()->encrypted_value);
        $this->assertSame($database->password, $environment->secrets()->where('key', 'PRIMARY_DATABASE_PASSWORD')->firstOrFail()->encrypted_value);
    }
}
