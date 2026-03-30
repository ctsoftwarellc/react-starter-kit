<?php

namespace Database\Factories;

use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Enums\TriggerType;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Models\PipelineRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PipelineRun> */
class PipelineRunFactory extends Factory
{
    protected $model = PipelineRun::class;

    public function definition(): array
    {
        return [
            'pipeline_id' => Pipeline::factory(),
            'status' => PipelineRunStatus::Pending,
            'trigger_type' => TriggerType::Push,
            'trigger_ref' => 'main',
            'trigger_sha' => fake()->sha1(),
            'trigger_actor' => fake()->userName(),
            'definition_snapshot' => [
                'artifact' => true,
                'stages' => [
                    [
                        'name' => 'build',
                        'jobs' => [
                            [
                                'name' => 'build',
                                'commands' => ['composer install', 'php artisan test'],
                                'environment' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
