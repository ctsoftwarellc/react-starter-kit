<?php

namespace Database\Factories;

use App\Modules\Infrastructure\Enums\ProviderType;
use App\Modules\Infrastructure\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Provider> */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => ProviderType::Manual,
            'credentials' => ['api_key' => 'test'],
            'is_active' => true,
        ];
    }

    public function digitalocean(): static
    {
        return $this->state(fn () => [
            'type' => ProviderType::DigitalOcean,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
