<?php

namespace Tests\Feature\Api\Pipeline;

use App\Models\User;
use App\Modules\Pipeline\Models\PipelineJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PipelineJobControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_job_and_read_log(): void
    {
        Storage::fake('artifacts');

        /** @var User $user */
        $user = User::factory()->create();
        $job = PipelineJob::factory()->create(['log_path' => 'run/job.log']);
        Storage::disk('artifacts')->put('logs/run/job.log', 'abcdef');

        $this->actingAs($user)
            ->getJson('/api/v1/pipeline-jobs/'.$job->id)
            ->assertOk()
            ->assertJsonPath('data.id', $job->id);

        $this->actingAs($user)
            ->getJson('/api/v1/pipeline-jobs/'.$job->id.'/log?offset=1&length=3')
            ->assertOk()
            ->assertJsonPath('content', 'bcd');
    }
}
