<?php

namespace Database\Factories;

use App\Modules\ServiceManagement\Models\StorageBucket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StorageBucket> */
class StorageBucketFactory extends Factory
{
    protected $model = StorageBucket::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'provider' => 's3',
            'region' => 'us-east-1',
            'access_key' => strtoupper(fake()->bothify('AKIA##########')),
            'secret_key' => fake()->sha256(),
            'bucket_name' => fake()->unique()->slug(3),
        ];
    }
}
