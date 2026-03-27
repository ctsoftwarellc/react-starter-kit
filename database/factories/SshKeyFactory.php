<?php

namespace Database\Factories;

use App\Models\SshKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SshKey> */
class SshKeyFactory extends Factory
{
    protected $model = SshKey::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word().' key',
            'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOMqqnkVzrm0SdG6UOoqKLsabgH5C9okWi0dh2l9GKJl test@example.com',
            'fingerprint' => 'SHA256:'.base64_encode(random_bytes(32)),
        ];
    }
}
