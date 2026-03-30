<?php

namespace Tests\Contract\Runner;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RunnerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_job_response_shape(): void
    {
        $runner = Runner::factory()->create(['status' => RunnerStatus::Online]);
        $job = PipelineJob::factory()->create([
            'status' => PipelineJobStatus::Queued,
            'pipeline_run_id' => PipelineRun::factory()->create([
                'trigger_ref' => 'main',
                'trigger_sha' => 'abc123',
                'definition_snapshot' => [
                    'artifact' => true,
                    'stages' => [[
                        'name' => 'build',
                        'jobs' => [[
                            'name' => 'build',
                            'commands' => ['npm run build'],
                            'timeout' => 15,
                        ]],
                    ]],
                ],
            ])->id,
        ]);

        $this->withToken($runner->token)
            ->getJson('/api/runner/jobs/next')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['job_id', 'pipeline_run_id', 'repository_url', 'ref', 'sha', 'commands', 'environment', 'services', 'artifact_config'],
            ])
            ->assertJsonPath('data.job_id', $job->id);
    }

    public function test_status_update_request_shape(): void
    {
        $runner = Runner::factory()->create(['status' => RunnerStatus::Busy]);
        $job = PipelineJob::factory()->create([
            'runner_id' => $runner->id,
            'status' => PipelineJobStatus::Assigned,
        ]);

        $this->withToken($runner->token)
            ->putJson('/api/runner/jobs/'.$job->id.'/status', [
                'status' => 'running',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'running');
    }

    public function test_log_upload_endpoint_accepts_plain_text_chunks(): void
    {
        Storage::fake('artifacts');

        $runner = Runner::factory()->create();
        $job = PipelineJob::factory()->create([
            'runner_id' => $runner->id,
            'status' => PipelineJobStatus::Running,
        ]);

        $this->withToken($runner->token)
            ->call('POST', '/api/runner/jobs/'.$job->id.'/log', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$runner->token], 'hello world')
            ->assertAccepted();

        $this->assertTrue(Storage::disk('artifacts')->exists('logs/'.$job->fresh()->log_path));
    }

    public function test_artifact_upload_endpoint_accepts_binary_stream(): void
    {
        Storage::fake('artifacts');

        $runner = Runner::factory()->create();
        $run = PipelineRun::factory()->create([
            'definition_snapshot' => [
                'artifact' => true,
                'stages' => [[
                    'name' => 'build',
                    'jobs' => [[
                        'name' => 'build',
                        'commands' => ['npm run build'],
                    ]],
                ]],
            ],
        ]);
        $job = PipelineJob::factory()->create([
            'pipeline_run_id' => $run->id,
            'runner_id' => $runner->id,
            'status' => PipelineJobStatus::Running,
        ]);

        $this->withToken($runner->token)
            ->call('POST', '/api/runner/jobs/'.$job->id.'/artifact', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$runner->token], 'binary-artifact')
            ->assertOk()
            ->assertJsonPath('data.pipeline_run_id', $run->id);
    }

    public function test_heartbeat_request_shape(): void
    {
        $runner = Runner::factory()->create(['status' => RunnerStatus::Offline]);

        $this->withToken($runner->token)
            ->postJson('/api/runner/heartbeat', [
                'platform' => 'linux/arm64',
                'metadata' => ['cpu_percent' => 12.5],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'online')
            ->assertJsonPath('data.platform', 'linux/arm64');
    }
}
