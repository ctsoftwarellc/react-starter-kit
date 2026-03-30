<?php

namespace Database\Factories;

use App\Modules\Pipeline\Enums\PipelineJobStatus;
use App\Modules\Pipeline\Models\PipelineJob;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PipelineJob> */
class PipelineJobFactory extends Factory
{
    protected $model = PipelineJob::class;

    public function definition(): array
    {
        return [
            'pipeline_run_id' => PipelineRun::factory(),
            'stage' => 'build',
            'name' => fake()->words(2, true),
            'status' => PipelineJobStatus::Pending,
            'commands' => ['composer install', 'php artisan test'],
            'environment' => [],
        ];
    }
}
