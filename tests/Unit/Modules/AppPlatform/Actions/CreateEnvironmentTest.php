<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\CreateEnvironment;
use App\Modules\AppPlatform\DTOs\CreateEnvironmentData;
use App\Modules\AppPlatform\Enums\EnvironmentType;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_environment_for_application(): void
    {
        Event::fake();

        $application = Application::factory()->create();
        $cluster = Cluster::factory()->create();

        $environment = (new CreateEnvironment)->execute($application, new CreateEnvironmentData(
            clusterId: $cluster->id,
            name: 'production',
            type: EnvironmentType::Production,
            isAutoDeploy: true,
            branch: 'main',
        ));

        $this->assertDatabaseHas('environments', [
            'id' => $environment->id,
            'application_id' => $application->id,
            'cluster_id' => $cluster->id,
            'name' => 'production',
        ]);

        Event::assertDispatched(EnvironmentConfigChanged::class);
    }

    public function test_it_enforces_unique_name_per_application(): void
    {
        $application = Application::factory()->create();
        $cluster = Cluster::factory()->create();

        (new CreateEnvironment)->execute($application, new CreateEnvironmentData(
            clusterId: $cluster->id,
            name: 'production',
            type: EnvironmentType::Production,
        ));

        $this->expectException(QueryException::class);

        (new CreateEnvironment)->execute($application, new CreateEnvironmentData(
            clusterId: $cluster->id,
            name: 'production',
            type: EnvironmentType::Staging,
        ));
    }
}
