<?php

namespace Database\Factories;

use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeploymentStep> */
class DeploymentStepFactory extends Factory
{
    protected $model = DeploymentStep::class;

    public function definition(): array
    {
        return [
            'deployment_id' => Deployment::factory(),
            'server_id' => Server::factory(),
            'status' => DeploymentStepStatus::Pending,
            'output' => null,
        ];
    }
}
