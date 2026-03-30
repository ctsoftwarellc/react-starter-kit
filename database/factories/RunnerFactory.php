<?php

namespace Database\Factories;

use App\Modules\Pipeline\Enums\RunnerStatus;
use App\Modules\Pipeline\Models\Runner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Runner> */
class RunnerFactory extends Factory
{
    protected $model = Runner::class;

    public function definition(): array
    {
        $token = Str::random(64);

        return [
            'name' => 'runner-'.fake()->unique()->numberBetween(1, 999),
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'status' => RunnerStatus::Online,
            'platform' => 'linux/amd64',
            'last_heartbeat_at' => now(),
            'metadata' => [],
        ];
    }
}
