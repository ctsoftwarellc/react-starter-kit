<?php

namespace Tests\Unit\Modules\Pipeline;

use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PipelineRunStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_run_transitions(): void
    {
        $run = PipelineRun::factory()->create(['status' => PipelineRunStatus::Pending]);
        $run->transitionTo(PipelineRunStatus::Running);
        $this->assertEquals(PipelineRunStatus::Running, $run->fresh()->status);

        $running = PipelineRun::factory()->create(['status' => PipelineRunStatus::Running]);
        foreach ([PipelineRunStatus::Succeeded, PipelineRunStatus::Failed, PipelineRunStatus::Cancelled, PipelineRunStatus::TimedOut] as $status) {
            $candidate = $running->replicate()->fill(['id' => null, 'status' => PipelineRunStatus::Running]);
            $candidate->save();
            $candidate->transitionTo($status);
            $this->assertEquals($status, $candidate->fresh()->status);
        }
    }

    public function test_invalid_run_transition_throws(): void
    {
        $run = PipelineRun::factory()->create(['status' => PipelineRunStatus::Pending]);

        $this->expectException(InvalidArgumentException::class);

        $run->transitionTo(PipelineRunStatus::Succeeded);
    }
}
