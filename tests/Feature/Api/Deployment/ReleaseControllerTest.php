<?php

namespace Tests\Feature\Api\Deployment;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_environment_releases(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);

        Release::factory()->count(2)->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id.'/releases')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_shows_a_release(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $release = Release::factory()->create([
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'environment_id' => Environment::factory()->create(['application_id' => $application->id])->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/releases/'.$release->id)
            ->assertOk()
            ->assertJsonPath('data.id', $release->id);
    }
}
