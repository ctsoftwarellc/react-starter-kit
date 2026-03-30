<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PipelineRunPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_detail_page_renders_grouped_stage_data(): void
    {
        $this->withoutVite();

        /** @var User $user */
        $user = User::factory()->create();
        $run = PipelineRun::factory()->create();
        PipelineJob::factory()->create(['pipeline_run_id' => $run->id, 'stage' => 'build', 'name' => 'compile']);
        PipelineJob::factory()->create(['pipeline_run_id' => $run->id, 'stage' => 'deploy', 'name' => 'release']);

        $this->actingAs($user)
            ->get('/pipeline-runs/'.$run->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('pipeline-runs/show')
                ->where('pipelineRun.id', $run->id)
                ->has('stages', 2));
    }
}
