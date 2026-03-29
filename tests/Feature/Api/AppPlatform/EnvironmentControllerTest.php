<?php

namespace Tests\Feature\Api\AppPlatform;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_application_environments(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        Environment::factory()->create(['application_id' => $application->id, 'name' => 'production']);
        Environment::factory()->create(['application_id' => $application->id, 'name' => 'staging']);

        $this->actingAs($user)
            ->getJson('/api/v1/applications/'.$application->id.'/environments')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_environment(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $cluster = Cluster::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/applications/'.$application->id.'/environments', [
                'cluster_id' => $cluster->id,
                'name' => 'staging',
                'type' => 'staging',
                'is_auto_deploy' => true,
                'branch' => 'develop',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'staging');
    }

    public function test_can_show_environment(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id)
            ->assertOk()
            ->assertJsonPath('data.id', $environment->id);
    }

    public function test_can_update_environment(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create(['name' => 'staging']);

        $this->actingAs($user)
            ->putJson('/api/v1/environments/'.$environment->id, [
                'name' => 'preview',
                'type' => 'preview',
                'is_auto_deploy' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'preview')
            ->assertJsonPath('data.type', 'preview');
    }

    public function test_can_delete_environment(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/api/v1/environments/'.$environment->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('environments', ['id' => $environment->id]);
    }

    public function test_duplicate_environment_name_within_application_returns_422(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $cluster = Cluster::factory()->create();
        Environment::factory()->create([
            'application_id' => $application->id,
            'cluster_id' => $cluster->id,
            'name' => 'production',
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/applications/'.$application->id.'/environments', [
                'cluster_id' => $cluster->id,
                'name' => 'production',
                'type' => 'production',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
