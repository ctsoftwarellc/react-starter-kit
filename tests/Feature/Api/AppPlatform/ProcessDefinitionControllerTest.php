<?php

namespace Tests\Feature\Api\AppPlatform;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\ProcessDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessDefinitionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_process_definitions(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        ProcessDefinition::factory()->count(2)->create(['environment_id' => $environment->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id.'/processes')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_process_definition(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/processes', [
                'type' => 'worker',
                'command' => 'php artisan queue:work',
                'instances' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'worker');
    }

    public function test_can_update_process_definition(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $process = ProcessDefinition::factory()->create(['environment_id' => $environment->id]);

        $this->actingAs($user)
            ->putJson('/api/v1/environments/'.$environment->id.'/processes/'.$process->id, [
                'type' => 'scheduler',
                'command' => 'php artisan schedule:run',
                'instances' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.type', 'scheduler')
            ->assertJsonPath('data.instances', 3);
    }

    public function test_can_delete_process_definition(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $process = ProcessDefinition::factory()->create(['environment_id' => $environment->id]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/environments/'.$environment->id.'/processes/'.$process->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('process_definitions', ['id' => $process->id]);
    }
}
