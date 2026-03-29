<?php

namespace Tests\Unit\Modules\AppPlatform\Listeners;

use App\Modules\AppPlatform\Enums\EnvironmentType;
use App\Modules\AppPlatform\Events\ApplicationCreated;
use App\Modules\AppPlatform\Listeners\CreateDefaultEnvironment;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateDefaultEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_default_environment_when_application_is_created(): void
    {
        $application = Application::factory()->create(['repository_branch' => 'main']);
        $cluster = Cluster::factory()->create();

        (new CreateDefaultEnvironment)->handle(new ApplicationCreated($application, $cluster->id));

        $this->assertDatabaseHas('environments', [
            'application_id' => $application->id,
            'cluster_id' => $cluster->id,
            'name' => 'production',
            'type' => EnvironmentType::Production->value,
            'branch' => 'main',
        ]);
    }
}
