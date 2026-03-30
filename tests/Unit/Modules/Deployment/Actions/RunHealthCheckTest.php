<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\RunHealthCheck;
use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Deployment\Models\HealthCheck;
use App\Modules\Deployment\Models\Release;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RunHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_passes_after_successful_http_checks(): void
    {
        Http::fake([
            '*' => Http::sequence()->pushStatus(200)->pushStatus(200),
        ]);

        [$deployment, $step] = $this->deploymentFixture();
        $healthCheck = HealthCheck::factory()->create([
            'environment_id' => $deployment->environment_id,
            'interval_seconds' => 0,
            'timeout_seconds' => 1,
            'healthy_threshold' => 2,
            'unhealthy_threshold' => 2,
        ]);

        $result = (new RunHealthCheck)->execute($deployment, $step, $healthCheck);

        $this->assertTrue($result);
        $this->assertSame(DeploymentStepStatus::Active, $step->fresh()->status);
    }

    public function test_it_fails_after_unhealthy_threshold_is_reached(): void
    {
        Http::fake([
            '*' => Http::sequence()->pushStatus(500)->pushStatus(500),
        ]);

        [$deployment, $step] = $this->deploymentFixture();
        $healthCheck = HealthCheck::factory()->create([
            'environment_id' => $deployment->environment_id,
            'interval_seconds' => 0,
            'timeout_seconds' => 1,
            'healthy_threshold' => 3,
            'unhealthy_threshold' => 2,
        ]);

        $result = (new RunHealthCheck)->execute($deployment, $step, $healthCheck);

        $this->assertFalse($result);
        $this->assertSame(DeploymentStepStatus::Failed, $step->fresh()->status);
    }

    public function test_it_treats_missing_health_check_as_pass_for_mvp(): void
    {
        [$deployment, $step] = $this->deploymentFixture();

        $result = (new RunHealthCheck)->execute($deployment, $step);

        $this->assertTrue($result);
        $this->assertSame(DeploymentStepStatus::Active, $step->fresh()->status);
    }

    private function deploymentFixture(): array
    {
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $release = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Deploying,
        ]);
        $deployment = Deployment::factory()->create([
            'release_id' => $release->id,
            'environment_id' => $environment->id,
        ]);
        $step = DeploymentStep::factory()->create([
            'deployment_id' => $deployment->id,
            'server_id' => Server::factory()->create()->id,
        ]);

        return [$deployment->fresh(), $step->fresh()];
    }
}
