<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Models\Pipeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Pipeline> */
class PipelineFactory extends Factory
{
    protected $model = Pipeline::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'name' => fake()->words(2, true),
            'definition' => [
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
            'is_active' => true,
            'trigger_branches' => ['main'],
            'trigger_events' => ['push'],
        ];
    }
}
