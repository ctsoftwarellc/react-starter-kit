<?php

namespace Tests\Feature\Web\Deployment;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\HealthCheck;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnvironmentDeploymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_environment_page_shows_active_release_and_recent_deployments(): void
    {
        $this->withoutVite();

        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $release = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
        ]);
        $environment->update(['active_release_id' => $release->id]);
        Deployment::factory()->count(2)->create([
            'environment_id' => $environment->id,
            'release_id' => $release->id,
        ]);

        $this->actingAs($user)
            ->get('/environments/'.$environment->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('environments/show')
                ->where('activeRelease.id', $release->id)
                ->has('deployments', 2));
    }

    public function test_user_can_submit_deploy_from_environment_page(): void
    {
        Bus::fake();

        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $artifact = Artifact::factory()->create(['application_id' => $application->id]);

        $response = $this->actingAs($user)->post('/environments/'.$environment->id.'/deploy', [
            'artifact_id' => $artifact->id,
            'strategy' => 'rolling',
        ]);

        $deployment = Deployment::query()->latest('created_at')->first();

        $response->assertRedirect(route('deployments.show', $deployment));
        $this->assertNotNull($deployment);
    }

    public function test_user_can_update_health_check_configuration(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $healthCheck = HealthCheck::factory()->create(['environment_id' => $environment->id]);

        $this->actingAs($user)
            ->put('/environments/'.$environment->id.'/health-check', [
                'id' => $healthCheck->id,
                'type' => 'http',
                'target' => 'https://example.test/up',
                'interval_seconds' => 10,
                'timeout_seconds' => 2,
                'healthy_threshold' => 2,
                'unhealthy_threshold' => 1,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('health_checks', [
            'id' => $healthCheck->id,
            'target' => 'https://example.test/up',
            'interval_seconds' => 10,
        ]);
    }
}
