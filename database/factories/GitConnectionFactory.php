<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\GitConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GitConnection> */
class GitConnectionFactory extends Factory
{
    protected $model = GitConnection::class;

    public function definition(): array
    {
        return [
            'provider' => GitProvider::Github,
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->optional()->sha256(),
            'token_expires_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'account_name' => fake()->userName(),
        ];
    }
}
