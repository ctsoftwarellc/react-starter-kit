<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStrategy;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\Release;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Deployment> */
class DeploymentFactory extends Factory
{
    protected $model = Deployment::class;

    public function definition(): array
    {
        return [
            'release_id' => Release::factory(),
            'environment_id' => Environment::factory(),
            'status' => DeploymentStatus::Pending,
            'strategy' => DeploymentStrategy::Rolling,
            'total_nodes' => 0,
            'completed_nodes' => 0,
            'failed_nodes' => 0,
        ];
    }
}
