<?php

namespace Tests\Unit\Modules\Pipeline;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PipelineJobStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_job_transitions(): void
    {
        $job = PipelineJob::factory()->create(['status' => PipelineJobStatus::Pending]);
        $job->transitionTo(PipelineJobStatus::Queued);
        $this->assertEquals(PipelineJobStatus::Queued, $job->fresh()->status);

        $job->transitionTo(PipelineJobStatus::Cancelled);
        $this->assertEquals(PipelineJobStatus::Cancelled, $job->fresh()->status);

        $job = PipelineJob::factory()->create(['status' => PipelineJobStatus::Pending]);
        $job->transitionTo(PipelineJobStatus::Skipped);
        $this->assertEquals(PipelineJobStatus::Skipped, $job->fresh()->status);
    }

    public function test_invalid_job_transition_throws(): void
    {
        $job = PipelineJob::factory()->create(['status' => PipelineJobStatus::Queued]);

        $this->expectException(InvalidArgumentException::class);

        $job->transitionTo(PipelineJobStatus::Succeeded);
    }
}
