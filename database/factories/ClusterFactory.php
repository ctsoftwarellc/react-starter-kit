<?php

namespace Database\Factories;

use App\Modules\Infrastructure\Enums\ClusterStatus;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Cluster> */
class ClusterFactory extends Factory
{
    protected $model = Cluster::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => ClusterStatus::Active,
            'settings' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ClusterStatus::Pending,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ClusterStatus::Active,
        ]);
    }
}
