<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\ServiceManagement\Actions\BindServiceToEnvironment;
use App\Modules\ServiceManagement\DTOs\BindServiceData;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithServiceManagement;
use Tests\TestCase;

class BindServiceToEnvironmentTest extends TestCase
{
    use InteractsWithServiceManagement;
    use RefreshDatabase;

    public function test_it_creates_a_service_binding(): void
    {
        $environment = Environment::factory()->create();
        $database = DatabaseInstance::factory()->create();

        $binding = (new BindServiceToEnvironment)->execute($environment, new BindServiceData(
            serviceType: ServiceType::Database,
            bindingName: 'Primary Database',
            databaseInstanceId: $database->id,
            config: ['ssl' => true],
        ));

        $this->assertDatabaseHas('service_bindings', [
            'id' => $binding->id,
            'environment_id' => $environment->id,
            'service_type' => ServiceType::Database->value,
            'database_instance_id' => $database->id,
            'binding_name' => 'Primary Database',
        ]);
    }

    public function test_it_creates_environment_secrets_for_the_bound_service(): void
    {
        $environment = Environment::factory()->create();
        $database = DatabaseInstance::factory()->create([
            'engine' => 'postgres',
            'host' => '10.0.0.10',
            'port' => 5432,
            'database_name' => 'orders',
            'username' => 'orders_user',
            'password' => 'orders-password',
        ]);

        (new BindServiceToEnvironment)->execute($environment, new BindServiceData(
            serviceType: ServiceType::Database,
            bindingName: 'Primary Database',
            databaseInstanceId: $database->id,
        ));

        $secrets = $this->createEnvironmentSecretsMap($environment);

        $this->assertSame('postgres', $secrets['PRIMARY_DATABASE_CONNECTION']);
        $this->assertSame('10.0.0.10', $secrets['PRIMARY_DATABASE_HOST']);
        $this->assertSame('5432', $secrets['PRIMARY_DATABASE_PORT']);
        $this->assertSame('orders', $secrets['PRIMARY_DATABASE_DATABASE']);
        $this->assertSame('orders_user', $secrets['PRIMARY_DATABASE_USERNAME']);
        $this->assertSame('orders-password', $secrets['PRIMARY_DATABASE_PASSWORD']);
    }
}
