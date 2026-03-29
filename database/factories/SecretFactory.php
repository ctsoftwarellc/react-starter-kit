<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Secret> */
class SecretFactory extends Factory
{
    protected $model = Secret::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'key' => strtoupper(fake()->unique()->lexify('SECRET_????')),
            'encrypted_value' => fake()->password(24),
            'version' => 1,
        ];
    }
}
