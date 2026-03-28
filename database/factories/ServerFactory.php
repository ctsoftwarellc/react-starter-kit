<?php

namespace Database\Factories;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Server> */
class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word().'-'.fake()->numberBetween(1, 99),
            'hostname' => fake()->domainName(),
            'public_ip' => fake()->ipv4(),
            'ssh_port' => 22,
            'ssh_user' => 'root',
            'status' => ServerStatus::Active,
            'metadata' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ServerStatus::Pending,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ServerStatus::Active,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ServerStatus::Failed,
        ]);
    }

    public function bootstrapping(): static
    {
        return $this->state(fn () => [
            'status' => ServerStatus::Bootstrapping,
        ]);
    }
}
