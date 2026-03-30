<?php

namespace Tests\Unit\Modules\Pipeline\Services;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Modules\Pipeline\Services\PipelineOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_only_the_first_stage_initially(): void
    {
        $run = PipelineRun::factory()->create([
            'status' => PipelineRunStatus::Pending,
            'definition_snapshot' => $this->definition(),
        ]);

        $run = (new PipelineOrchestrator)->orchestrate($run);

        $this->assertEquals(PipelineRunStatus::Running, $run->fresh()->status);
        $this->assertDatabaseHas('pipeline_jobs', ['pipeline_run_id' => $run->id, 'name' => 'lint', 'status' => 'queued']);
        $this->assertDatabaseHas('pipeline_jobs', ['pipeline_run_id' => $run->id, 'name' => 'build', 'status' => 'pending']);
    }

    public function test_it_advances_to_the_next_stage_after_all_jobs_succeed(): void
    {
        $run = PipelineRun::factory()->create([
            'status' => PipelineRunStatus::Running,
            'definition_snapshot' => $this->definition(),
        ]);
        PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'stage' => 'prepare',
            'name' => 'lint',
            'status' => PipelineJobStatus::Succeeded,
        ]);
        $nextJob = PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'stage' => 'build',
            'name' => 'build',
            'status' => PipelineJobStatus::Pending,
        ]);

        (new PipelineOrchestrator)->queueReadyStageJobs($run->fresh('jobs'));

        $this->assertEquals(PipelineJobStatus::Queued, $nextJob->fresh()->status);
    }

    public function test_it_skips_remaining_stages_after_required_job_failure(): void
    {
        $run = PipelineRun::factory()->create([
            'status' => PipelineRunStatus::Running,
            'definition_snapshot' => $this->definition(),
        ]);
        PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'stage' => 'prepare',
            'name' => 'lint',
            'status' => PipelineJobStatus::Failed,
        ]);
        $pending = PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'stage' => 'build',
            'name' => 'build',
            'status' => PipelineJobStatus::Pending,
        ]);

        $result = (new PipelineOrchestrator)->syncRunState($run->fresh('jobs'));

        $this->assertEquals(PipelineRunStatus::Failed, $result->fresh()->status);
        $this->assertEquals(PipelineJobStatus::Skipped, $pending->fresh()->status);
    }

    private function definition(): array
    {
        return [
            'artifact' => false,
            'stages' => [
                ['name' => 'prepare', 'jobs' => [['name' => 'lint', 'commands' => ['pint']]]],
                ['name' => 'build', 'jobs' => [['name' => 'build', 'commands' => ['npm run build']]]],
            ],
        ];
    }
}
