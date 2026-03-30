<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Actions\FailPipelineJob;
use App\Modules\Pipeline\DTOs\FailPipelineJobData;
use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Modules\Pipeline\Events\PipelineJobCompleted;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FailPipelineJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_a_running_job_failed_and_emits_completion_event(): void
    {
        Event::fake();

        $run = PipelineRun::factory()->create(['status' => PipelineRunStatus::Running]);
        $runner = Runner::factory()->create(['status' => RunnerStatus::Busy]);
        $job = PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'runner_id' => $runner->id,
            'status' => PipelineJobStatus::Running,
        ]);

        $failed = (new FailPipelineJob)->execute($job, new FailPipelineJobData(
            status: PipelineJobStatus::Failed,
            exitCode: 1,
            metadata: ['message' => 'boom'],
        ));

        $this->assertEquals(PipelineJobStatus::Failed, $failed->status);
        $this->assertSame(1, $failed->exit_code);
        $this->assertSame('boom', $failed->environment['message']);
        $this->assertEquals(RunnerStatus::Online, $runner->fresh()->status);

        Event::assertDispatched(PipelineJobCompleted::class);
    }

    public function test_it_marks_a_job_timed_out_when_requested(): void
    {
        $run = PipelineRun::factory()->create(['status' => PipelineRunStatus::Running]);
        $job = PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'status' => PipelineJobStatus::Running,
        ]);

        $timedOut = (new FailPipelineJob)->execute($job, new FailPipelineJobData(
            status: PipelineJobStatus::TimedOut,
        ));

        $this->assertEquals(PipelineJobStatus::TimedOut, $timedOut->status);
    }
}
