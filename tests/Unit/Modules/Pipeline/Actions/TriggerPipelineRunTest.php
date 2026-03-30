<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Actions\TriggerPipelineRun;
use App\Modules\Pipeline\DTOs\TriggerPipelineRunData;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Enums\TriggerType;
use App\Modules\Pipeline\Jobs\OrchestrateRun;
use App\Modules\Pipeline\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class TriggerPipelineRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_snapshots_the_definition_and_dispatches_orchestration(): void
    {
        Bus::fake();

        $pipeline = Pipeline::factory()->create();
        $pipeline->update(['definition' => [
            'artifact' => true,
            'stages' => [[
                'name' => 'build',
                'jobs' => [[
                    'name' => 'compile',
                    'commands' => ['npm ci', 'npm run build'],
                ]],
            ]],
        ]]);

        $run = (new TriggerPipelineRun)->execute($pipeline, new TriggerPipelineRunData(
            triggerType: TriggerType::Manual,
            triggerRef: 'main',
            triggerSha: 'abc123',
            triggerActor: 'caleb',
        ));

        $this->assertEquals(PipelineRunStatus::Pending, $run->status);
        $this->assertSame($pipeline->definition, $run->definition_snapshot);

        Bus::assertDispatched(OrchestrateRun::class, fn (OrchestrateRun $job) => $job->pipelineRun->is($run));
    }

    public function test_it_leaves_subsequent_runs_pending_when_another_run_is_active(): void
    {
        Bus::fake();

        $pipeline = Pipeline::factory()->create();
        $first = (new TriggerPipelineRun)->execute($pipeline, new TriggerPipelineRunData(triggerType: TriggerType::Push));
        $second = (new TriggerPipelineRun)->execute($pipeline, new TriggerPipelineRunData(triggerType: TriggerType::Push));

        $this->assertEquals(PipelineRunStatus::Pending, $first->status);
        $this->assertEquals(PipelineRunStatus::Pending, $second->status);
        $this->assertDatabaseCount('pipeline_runs', 2);
    }
}
