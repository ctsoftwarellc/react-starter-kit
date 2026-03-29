<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\EnvironmentVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EnvironmentVariable> */
class EnvironmentVariableFactory extends Factory
{
    protected $model = EnvironmentVariable::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'key' => strtoupper(fake()->unique()->lexify('APP_????')),
            'value' => fake()->word(),
            'is_build_arg' => false,
        ];
    }
}
