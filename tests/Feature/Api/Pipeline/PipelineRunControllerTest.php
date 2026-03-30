<?php

namespace Tests\Feature\Api\Pipeline;

use App\Models\User;
use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PipelineRunControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_cancel_and_retry_pipeline_runs(): void
    {
        Queue::fake();

        /** @var User $user */
        $user = User::factory()->create();
        $run = PipelineRun::factory()->create(['status' => PipelineRunStatus::Running]);
        PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'status' => PipelineJobStatus::Running,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/pipeline-runs/'.$run->id)
            ->assertOk()
            ->assertJsonPath('data.id', $run->id);

        $this->actingAs($user)
            ->postJson('/api/v1/pipeline-runs/'.$run->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->actingAs($user)
            ->postJson('/api/v1/pipeline-runs/'.$run->id.'/retry')
            ->assertCreated()
            ->assertJsonPath('data.pipeline_id', $run->pipeline_id);
    }
}
