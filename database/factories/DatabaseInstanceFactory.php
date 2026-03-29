<?php

namespace Database\Factories;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\Enums\DatabaseEngine;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DatabaseInstance> */
class DatabaseInstanceFactory extends Factory
{
    protected $model = DatabaseInstance::class;

    public function definition(): array
    {
        return [
            'cluster_id' => Cluster::factory(),
            'name' => fake()->unique()->slug(2),
            'engine' => DatabaseEngine::Postgres,
            'version' => '16',
            'host' => fake()->ipv4(),
            'port' => 5432,
            'database_name' => fake()->slug(),
            'username' => fake()->userName(),
            'password' => fake()->password(24),
        ];
    }
}
