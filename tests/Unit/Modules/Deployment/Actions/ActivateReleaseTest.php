<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\ActivateRelease;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Events\DeploymentCompleted;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Enums\ArtifactStatus;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ActivateReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_release_active_and_updates_environment_active_release(): void
    {
        Event::fake([DeploymentCompleted::class]);

        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $artifact = Artifact::factory()->create([
            'application_id' => $application->id,
            'status' => ArtifactStatus::Ready,
        ]);
        $release = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => $artifact->id,
            'status' => ReleaseStatus::Deploying,
        ]);
        $deployment = Deployment::factory()->create([
            'release_id' => $release->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Verifying,
        ]);

        (new ActivateRelease)->execute($deployment);

        $this->assertSame(DeploymentStatus::Succeeded, $deployment->fresh()->status);
        $this->assertSame(ReleaseStatus::Active, $release->fresh()->status);
        $this->assertSame($release->id, $environment->fresh()->active_release_id);
        $this->assertSame(ArtifactStatus::Deployed, $artifact->fresh()->status);

        Event::assertDispatched(DeploymentCompleted::class);
    }

    public function test_it_supersedes_the_previous_active_release(): void
    {
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $previousRelease = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Active,
        ]);
        $environment->update(['active_release_id' => $previousRelease->id]);

        $release = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Deploying,
        ]);
        $deployment = Deployment::factory()->create([
            'release_id' => $release->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Verifying,
        ]);

        (new ActivateRelease)->execute($deployment);

        $this->assertSame(ReleaseStatus::Superseded, $previousRelease->fresh()->status);
        $this->assertSame($release->id, $environment->fresh()->active_release_id);
    }
}
