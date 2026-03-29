<?php

namespace Tests\Unit\Modules\ServiceManagement\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\ServiceManagement\Actions\UnbindServiceFromEnvironment;
use App\Modules\ServiceManagement\Enums\ServiceType;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Models\ServiceBinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnbindServiceFromEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_the_service_binding(): void
    {
        $binding = ServiceBinding::factory()->create();

        (new UnbindServiceFromEnvironment)->execute($binding);

        $this->assertDatabaseMissing('service_bindings', ['id' => $binding->id]);
    }

    public function test_it_removes_generated_environment_secrets(): void
    {
        $environment = Environment::factory()->create();
        $database = DatabaseInstance::factory()->create();
        $binding = ServiceBinding::factory()->create([
            'environment_id' => $environment->id,
            'service_type' => ServiceType::Database,
            'database_instance_id' => $database->id,
            'binding_name' => 'Primary Database',
        ]);

        foreach (['CONNECTION', 'HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD'] as $suffix) {
            Secret::factory()->create([
                'environment_id' => $environment->id,
                'key' => 'PRIMARY_DATABASE_'.$suffix,
            ]);
        }

        (new UnbindServiceFromEnvironment)->execute($binding);

        $this->assertDatabaseCount('secrets', 0);
    }
}
