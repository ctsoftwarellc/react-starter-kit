<?php

namespace Tests\Feature\Api\AppPlatform;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\GitConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitConnectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_git_connections(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        GitConnection::factory()->count(2)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/git-connections');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('data.0.access_token');
    }

    public function test_can_delete_git_connection(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $connection = GitConnection::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/api/v1/git-connections/'.$connection->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('git_connections', ['id' => $connection->id]);
    }

    public function test_cannot_delete_git_connection_when_applications_are_attached(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $connection = GitConnection::factory()->create();
        Application::factory()->create(['git_connection_id' => $connection->id]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/git-connections/'.$connection->id)
            ->assertStatus(500);
    }

    public function test_unauthenticated_requests_return_401(): void
    {
        $this->getJson('/api/v1/git-connections')->assertUnauthorized();
    }
}
