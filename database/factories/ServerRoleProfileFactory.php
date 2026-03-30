<?php

namespace Database\Factories;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\ServerRoleProfile;
use App\Modules\Infrastructure\Enums\NodeRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServerRoleProfile> */
class ServerRoleProfileFactory extends Factory
{
    protected $model = ServerRoleProfile::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'role' => NodeRole::Web,
            'name' => 'Web Nodes',
            'config' => [
                'packages' => ['caddy', 'php8.3-fpm'],
                'services' => ['caddy', 'php8.3-fpm'],
            ],
        ];
    }
}
