<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Networking\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Domain> */
class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'hostname' => fake()->unique()->domainName(),
            'is_primary' => false,
            'is_verified' => false,
            'verification_token' => hash('sha256', Str::lower(Str::random(40))),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['is_verified' => true]);
    }
}
