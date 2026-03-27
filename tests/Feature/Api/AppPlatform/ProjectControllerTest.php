<?php

namespace Tests\Feature\Api\AppPlatform;

use App\Actions\CreatePersonalAccessToken;
use App\Models\User;
use App\Modules\AppPlatform\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(): array
    {
        $user = User::factory()->create();
        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        return [$user, $result->plainTextToken];
    }

    public function test_list_projects_returns_200(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        Project::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/projects');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_create_project_returns_201(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/projects', [
                'name' => 'New Project',
                'description' => 'A great project',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'New Project');
        $response->assertJsonPath('data.slug', 'new-project');
    }

    public function test_show_project_returns_200(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $project = Project::factory()->create(['name' => 'Test Project', 'slug' => 'test-project']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/projects/test-project');

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Test Project');
    }

    public function test_update_project_returns_200(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $project = Project::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/projects/old-name', [
                'name' => 'Updated Name',
                'description' => 'Updated desc',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('data.slug', 'updated-name');
    }

    public function test_delete_project_returns_204_soft_delete(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $project = Project::factory()->create(['slug' => 'to-delete']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/projects/to-delete');

        $response->assertNoContent();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_show_deleted_project_returns_404(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $project = Project::factory()->create(['slug' => 'deleted-project']);
        $project->delete();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/projects/deleted-project');

        $response->assertNotFound();
    }

    public function test_slug_uniqueness_works(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/projects', ['name' => 'My Project']);

        $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/projects', ['name' => 'My Project']);

        $response1->assertCreated();
        $response2->assertCreated();

        $this->assertNotEquals(
            $response1->json('data.slug'),
            $response2->json('data.slug')
        );
    }
}
