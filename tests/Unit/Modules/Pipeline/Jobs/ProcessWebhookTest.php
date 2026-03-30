<?php

namespace Tests\Unit\Modules\Pipeline\Jobs;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Jobs\OrchestrateRun;
use App\Modules\Pipeline\Jobs\ProcessWebhook;
use App\Modules\Pipeline\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_triggers_matching_pipelines(): void
    {
        Bus::fake();

        $application = Application::factory()->create();
        $matchingPipeline = Pipeline::factory()->for($application)->create([
            'trigger_branches' => ['main'],
            'trigger_events' => ['push'],
        ]);
        $nonMatchingPipeline = Pipeline::factory()->for($application)->create([
            'trigger_branches' => ['develop'],
            'trigger_events' => ['push'],
        ]);

        (new ProcessWebhook(
            application: $application,
            provider: 'github',
            payload: [
                'ref' => 'refs/heads/main',
                'after' => 'abc123',
                'sender' => ['login' => 'caleb'],
            ],
            headers: ['X-GitHub-Event' => ['push']],
        ))->handle();

        $this->assertDatabaseCount('pipeline_runs', 1);
        $this->assertDatabaseHas('pipeline_runs', [
            'pipeline_id' => $matchingPipeline->id,
            'trigger_ref' => 'main',
            'trigger_sha' => 'abc123',
        ]);
        $this->assertDatabaseMissing('pipeline_runs', [
            'pipeline_id' => $nonMatchingPipeline->id,
        ]);

        Bus::assertDispatched(OrchestrateRun::class, 1);
    }
}
