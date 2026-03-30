<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Models\ServerRoleProfile;
use App\Modules\Infrastructure\Enums\NodeRole;

class UpdateServerRoleProfile
{
    public function execute(ServerRoleProfile $serverRoleProfile, array $attributes): ServerRoleProfile
    {
        $serverRoleProfile->update([
            'role' => $attributes['role'] instanceof NodeRole ? $attributes['role'] : NodeRole::from($attributes['role']),
            'name' => $attributes['name'],
            'config' => $attributes['config'] ?? [],
        ]);

        return $serverRoleProfile->fresh();
    }
}
