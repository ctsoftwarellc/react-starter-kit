<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Enums\ArtifactStatus;
use App\Modules\Pipeline\Models\Artifact;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Artifact> */
class ArtifactFactory extends Factory
{
    protected $model = Artifact::class;

    public function definition(): array
    {
        return [
            'pipeline_run_id' => PipelineRun::factory()->for(Pipeline::factory()),
            'status' => ArtifactStatus::Ready,
            'storage_path' => 'pipeline-runs/'.Str::random(16).'/'.Str::random(16).'.tar.gz',
            'content_hash' => hash('sha256', Str::random(32)),
            'size_bytes' => fake()->numberBetween(1024, 1048576),
            'metadata' => [],
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Artifact $artifact): void {
            if ($artifact->application_id !== null) {
                return;
            }

            $run = $artifact->pipelineRun;

            if ($run !== null) {
                $artifact->application_id = $run->pipeline->application_id;

                return;
            }

            $artifact->application_id = Application::factory()->create()->id;
        });
    }
}
