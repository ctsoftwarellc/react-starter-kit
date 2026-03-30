<?php

namespace Tests\Feature\Api\Deployment;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Jobs\ExecuteDeployment;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeploymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_environment_deployments(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        Deployment::factory()->count(2)->create(['environment_id' => $environment->id]);
        Deployment::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id.'/deployments')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_initiates_a_deployment_from_an_artifact(): void
    {
        Bus::fake();

        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $artifact = Artifact::factory()->create(['application_id' => $application->id]);

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/deploy', [
                'artifact_id' => $artifact->id,
                'strategy' => 'rolling',
            ])
            ->assertCreated()
            ->assertJsonPath('data.environment_id', $environment->id)
            ->assertJsonPath('data.release.artifact_id', $artifact->id);

        $this->assertDatabaseHas('deployments', ['environment_id' => $environment->id]);
        Bus::assertDispatched(ExecuteDeployment::class, 1);
    }

    public function test_it_can_cancel_a_pending_deployment(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $deployment = Deployment::factory()->create(['status' => DeploymentStatus::Pending]);

        $this->actingAs($user)
            ->postJson('/api/v1/deployments/'.$deployment->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', DeploymentStatus::Cancelled->value);
    }

    public function test_it_can_start_a_manual_rollback_for_a_previous_release(): void
    {
        Bus::fake();

        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $activeRelease = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Active,
            'version' => 2,
        ]);
        $rollbackRelease = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Superseded,
            'version' => 1,
        ]);
        $environment->update(['active_release_id' => $activeRelease->id]);

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/rollback', [
                'release_id' => $rollbackRelease->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', DeploymentStatus::Pending->value);

        $rollbackDeployment = Deployment::query()->latest('created_at')->firstOrFail();
        $clonedRelease = $rollbackDeployment->release()->firstOrFail();

        $this->assertNotSame($rollbackRelease->id, $clonedRelease->id);
        $this->assertSame($rollbackRelease->artifact_id, $clonedRelease->artifact_id);
        $this->assertSame(ReleaseStatus::Deploying, $clonedRelease->fresh()->status);

        Bus::assertDispatched(ExecuteDeployment::class, 1);
    }
}
