<?php

namespace Database\Factories;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PersonalAccessToken> */
class PersonalAccessTokenFactory extends Factory
{
    protected $model = PersonalAccessToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word().' token',
            'token' => hash('sha256', Str::random(64)),
            'abilities' => ['*'],
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}
