<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Actions\AssignJobToRunner;
use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignJobToRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_a_queued_job_to_an_available_runner(): void
    {
        $job = PipelineJob::factory()->create(['status' => PipelineJobStatus::Queued]);
        $runner = Runner::factory()->create(['status' => RunnerStatus::Online]);

        $assigned = (new AssignJobToRunner)->execute($job, $runner);

        $this->assertEquals(PipelineJobStatus::Assigned, $assigned->status);
        $this->assertSame($runner->id, $assigned->runner_id);
        $this->assertEquals(RunnerStatus::Busy, $runner->fresh()->status);
    }
}
