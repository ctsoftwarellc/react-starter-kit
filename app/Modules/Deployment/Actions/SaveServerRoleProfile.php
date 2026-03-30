<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\ServerRoleProfile;
use Illuminate\Support\Facades\DB;

class SaveServerRoleProfile
{
    public function execute(Environment $environment, array $attributes, ?ServerRoleProfile $serverRoleProfile = null): ServerRoleProfile
    {
        return DB::transaction(function () use ($environment, $attributes, $serverRoleProfile) {
            $serverRoleProfile ??= $environment->serverRoleProfiles()
                ->where('role', $attributes['role'])
                ->first();

            $serverRoleProfile = $serverRoleProfile === null
                ? (new CreateServerRoleProfile)->execute($environment, $attributes)
                : (new UpdateServerRoleProfile)->execute($serverRoleProfile, $attributes);

            return (new ApplyServerRoleProfile)->execute($serverRoleProfile);
        });
    }
}
