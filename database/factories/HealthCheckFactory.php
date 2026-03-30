<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\HealthCheckType;
use App\Modules\Deployment\Models\HealthCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HealthCheck> */
class HealthCheckFactory extends Factory
{
    protected $model = HealthCheck::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'type' => HealthCheckType::Http,
            'target' => 'https://example.test/health',
            'interval_seconds' => 30,
            'timeout_seconds' => 5,
            'healthy_threshold' => 3,
            'unhealthy_threshold' => 2,
            'is_active' => true,
        ];
    }
}
