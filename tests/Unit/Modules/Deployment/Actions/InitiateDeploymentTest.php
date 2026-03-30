<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\InitiateDeployment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Events\DeploymentStarted;
use App\Modules\Deployment\Jobs\ExecuteDeployment;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InitiateDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_release_when_given_an_artifact(): void
    {
        Bus::fake();
        Event::fake([DeploymentStarted::class]);

        $user = User::factory()->create();
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $artifact = Artifact::factory()->create(['application_id' => $application->id]);

        $deployment = (new InitiateDeployment)->execute($environment, $artifact, initiatedBy: $user);

        $release = Release::query()->first();

        $this->assertNotNull($release);
        $this->assertSame($release->id, $deployment->release_id);
        $this->assertSame(ReleaseStatus::Deploying, $release->fresh()->status);
        $this->assertSame(DeploymentStatus::Pending, $deployment->status);

        Event::assertDispatched(DeploymentStarted::class, fn (DeploymentStarted $event) => $event->deployment->is($deployment));
        Bus::assertDispatched(ExecuteDeployment::class, fn (ExecuteDeployment $job) => $job->deployment->is($deployment));
    }

    public function test_it_creates_a_deployment_and_dispatches_execute_deployment(): void
    {
        Bus::fake();
        Event::fake([DeploymentStarted::class]);

        $user = User::factory()->create();
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $release = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Pending,
        ]);

        $deployment = (new InitiateDeployment)->execute($environment, $release, initiatedBy: $user);

        $this->assertDatabaseHas('deployments', [
            'id' => $deployment->id,
            'environment_id' => $environment->id,
            'release_id' => $release->id,
            'status' => DeploymentStatus::Pending->value,
            'initiated_by' => $user->id,
        ]);
        $this->assertSame(ReleaseStatus::Deploying, $release->fresh()->status);

        Event::assertDispatched(DeploymentStarted::class, fn (DeploymentStarted $event) => $event->deployment->id === $deployment->id);
        Bus::assertDispatched(ExecuteDeployment::class, 1);
    }
}
