<?php

namespace Tests\Unit\Modules\Pipeline\Actions;

use App\Modules\Pipeline\Actions\DeletePipeline;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DeletePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_an_inactive_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create();

        (new DeletePipeline)->execute($pipeline);

        $this->assertDatabaseMissing('pipelines', ['id' => $pipeline->id]);
    }

    public function test_it_rejects_deleting_a_pipeline_with_an_active_run(): void
    {
        $pipeline = Pipeline::factory()->create();
        PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => PipelineRunStatus::Running,
        ]);

        $this->expectException(RuntimeException::class);

        (new DeletePipeline)->execute($pipeline);
    }
}
