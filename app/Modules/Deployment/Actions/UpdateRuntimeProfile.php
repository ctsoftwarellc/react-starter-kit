<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Enums\RuntimeProfileStack;
use App\Modules\Deployment\Models\RuntimeProfile;

class UpdateRuntimeProfile
{
    public function execute(RuntimeProfile $runtimeProfile, array $attributes): RuntimeProfile
    {
        $runtimeProfile->update([
            'name' => $attributes['name'],
            'stack' => $attributes['stack'] instanceof RuntimeProfileStack ? $attributes['stack'] : RuntimeProfileStack::from($attributes['stack']),
            'config' => $attributes['config'] ?? [],
        ]);

        return $runtimeProfile->fresh();
    }
}
