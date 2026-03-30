<?php

namespace Tests\Feature\Api\Pipeline;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Jobs\OrchestrateRun;
use App\Modules\Pipeline\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PipelineControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_list_create_show_update_delete_and_trigger_pipelines(): void
    {
        Bus::fake();

        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();
        $pipeline = Pipeline::factory()->create(['application_id' => $application->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/applications/'.$application->id.'/pipelines')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/applications/'.$application->id.'/pipelines', [
                'name' => 'Deploy',
                'definition' => ['stages' => [['name' => 'build', 'jobs' => [['name' => 'build', 'commands' => ['npm run build']]]]]],
                'trigger_events' => ['manual'],
            ])
            ->assertCreated();

        $createdId = $response->json('data.id');

        $this->actingAs($user)
            ->getJson('/api/v1/pipelines/'.$pipeline->id)
            ->assertOk()
            ->assertJsonPath('data.id', $pipeline->id);

        $this->actingAs($user)
            ->putJson('/api/v1/pipelines/'.$pipeline->id, [
                'name' => 'Updated',
                'definition' => ['stages' => [['name' => 'test', 'jobs' => [['name' => 'phpunit', 'commands' => ['php artisan test']]]]]],
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated');

        $this->actingAs($user)
            ->postJson('/api/v1/pipelines/'.$pipeline->id.'/trigger', [
                'trigger_type' => 'manual',
                'trigger_ref' => 'main',
            ])
            ->assertCreated()
            ->assertJsonPath('data.pipeline_id', $pipeline->id);

        Bus::assertDispatched(OrchestrateRun::class);

        $this->actingAs($user)
            ->deleteJson('/api/v1/pipelines/'.$createdId)
            ->assertNoContent();
    }

    public function test_it_validates_pipeline_creation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/applications/'.$application->id.'/pipelines', ['name' => '', 'definition' => []])
            ->assertUnprocessable();
    }

    public function test_it_requires_authentication(): void
    {
        $application = Application::factory()->create();

        $this->getJson('/api/v1/applications/'.$application->id.'/pipelines')->assertUnauthorized();
    }
}
