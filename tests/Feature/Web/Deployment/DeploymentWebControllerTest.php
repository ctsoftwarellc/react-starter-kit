<?php

namespace Tests\Feature\Web\Deployment;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeploymentWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_page_shows_release_metadata_and_rollback_options(): void
    {
        $this->withoutVite();

        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $release = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'version' => 2,
        ]);
        $previousRelease = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'version' => 1,
        ]);
        $deployment = Deployment::factory()->create([
            'environment_id' => $environment->id,
            'release_id' => $release->id,
        ]);

        $this->actingAs($user)
            ->get(route('deployments.show', $deployment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('deployments/show')
                ->where('deployment.id', $deployment->id)
                ->where('release.id', $release->id)
                ->has('availableRollbackReleases', 1)
                ->where('availableRollbackReleases.0.id', $previousRelease->id));
    }
}
