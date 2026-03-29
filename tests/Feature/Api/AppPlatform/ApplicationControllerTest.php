<?php

namespace Tests\Feature\Api\AppPlatform;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\GitConnection;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_project_applications(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Application::factory()->count(2)->create(['project_id' => $project->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/projects/'.$project->slug.'/applications')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_application(): void
    {
        Event::fake();

        /** @var User $user */
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $connection = GitConnection::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/projects/'.$project->slug.'/applications', [
                'name' => 'Helm App',
                'runtime' => 'php',
                'repository_url' => 'https://github.com/caleb/helm',
                'repository_branch' => 'main',
                'git_connection_id' => $connection->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Helm App');
    }

    public function test_can_show_application(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/applications/'.$application->id)
            ->assertOk()
            ->assertJsonPath('data.id', $application->id);
    }

    public function test_can_update_application(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create(['name' => 'Old App']);

        $this->actingAs($user)
            ->putJson('/api/v1/applications/'.$application->id, [
                'name' => 'New App',
                'runtime' => 'node',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New App')
            ->assertJsonPath('data.runtime', 'node');
    }

    public function test_can_delete_application(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/applications/'.$application->id)
            ->assertNoContent();

        $this->assertSoftDeleted('applications', ['id' => $application->id]);
    }

    public function test_validation_fails_for_invalid_runtime(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/projects/'.$project->slug.'/applications', [
                'name' => 'Bad App',
                'runtime' => 'ruby',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['runtime']);
    }

    public function test_unauthenticated_requests_return_401(): void
    {
        $project = Project::factory()->create();

        $this->getJson('/api/v1/projects/'.$project->slug.'/applications')->assertUnauthorized();
    }
}
