<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\ServerRoleProfile;
use App\Modules\Infrastructure\Enums\NodeRole;

class CreateServerRoleProfile
{
    public function execute(Environment $environment, array $attributes): ServerRoleProfile
    {
        return $environment->serverRoleProfiles()->create([
            'role' => $attributes['role'] instanceof NodeRole ? $attributes['role'] : NodeRole::from($attributes['role']),
            'name' => $attributes['name'],
            'config' => $attributes['config'] ?? [],
        ]);
    }
}
