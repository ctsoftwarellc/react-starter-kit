<?php

namespace Tests\Feature\Api\Pipeline;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtifactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_artifacts_for_application_and_show_artifact_detail(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $artifact = Artifact::factory()->create(['application_id' => $application->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/applications/'.$application->id.'/artifacts')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($user)
            ->getJson('/api/v1/artifacts/'.$artifact->id)
            ->assertOk()
            ->assertJsonPath('data.id', $artifact->id);
    }
}
