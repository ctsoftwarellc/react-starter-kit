<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Actions\CompletePipelineJob;
use App\Modules\Pipeline\DTOs\CompletePipelineJobData;
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

class CompletePipelineJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_a_running_job_succeeded_and_emits_completion_event(): void
    {
        Event::fake();

        $run = PipelineRun::factory()->create(['status' => PipelineRunStatus::Running]);
        $runner = Runner::factory()->create(['status' => RunnerStatus::Busy]);
        $job = PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'runner_id' => $runner->id,
            'status' => PipelineJobStatus::Running,
            'environment' => ['branch' => 'main'],
        ]);

        $completed = (new CompletePipelineJob)->execute($job, new CompletePipelineJobData(
            exitCode: 0,
            metadata: ['artifact' => 'ready'],
        ));

        $this->assertEquals(PipelineJobStatus::Succeeded, $completed->status);
        $this->assertSame(0, $completed->exit_code);
        $this->assertSame('ready', $completed->environment['artifact']);
        $this->assertEquals(RunnerStatus::Online, $runner->fresh()->status);

        Event::assertDispatched(PipelineJobCompleted::class, fn (PipelineJobCompleted $event) => $event->pipelineJob->id === $job->id);
    }
}
