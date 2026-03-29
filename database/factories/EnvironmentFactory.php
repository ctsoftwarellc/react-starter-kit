<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Enums\EnvironmentType;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Environment> */
class EnvironmentFactory extends Factory
{
    protected $model = Environment::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'cluster_id' => Cluster::factory(),
            'name' => 'production',
            'type' => EnvironmentType::Production,
            'is_auto_deploy' => false,
            'branch' => 'main',
        ];
    }
}
