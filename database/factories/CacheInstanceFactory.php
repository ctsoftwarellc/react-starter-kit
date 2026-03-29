<?php

namespace Database\Factories;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\ServiceManagement\Enums\CacheEngine;
use App\Modules\ServiceManagement\Models\CacheInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CacheInstance> */
class CacheInstanceFactory extends Factory
{
    protected $model = CacheInstance::class;

    public function definition(): array
    {
        return [
            'cluster_id' => Cluster::factory(),
            'name' => fake()->unique()->slug(2),
            'engine' => CacheEngine::Redis,
            'version' => '7',
            'host' => fake()->ipv4(),
            'port' => 6379,
            'password' => fake()->password(24),
        ];
    }
}
