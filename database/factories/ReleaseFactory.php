<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Models\Release;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Release> */
class ReleaseFactory extends Factory
{
    protected $model = Release::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'artifact_id' => Artifact::factory(),
            'version' => fake()->numberBetween(1, 20),
            'status' => ReleaseStatus::Pending,
            'config_snapshot' => [
                'env_vars' => ['APP_ENV' => 'production'],
                'secrets' => ['APP_KEY'],
                'processes' => [
                    ['type' => 'web', 'command' => 'php-fpm', 'instances' => 1],
                ],
                'runtime' => 'php',
                'php_version' => '8.3',
            ],
        ];
    }
}
