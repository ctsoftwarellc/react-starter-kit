<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Deployment\Enums\RuntimeProfileStack;
use App\Modules\Deployment\Models\RuntimeProfile;

class CreateRuntimeProfile
{
    public function execute(Application $application, array $attributes): RuntimeProfile
    {
        return $application->runtimeProfiles()->create([
            'name' => $attributes['name'],
            'stack' => $attributes['stack'] instanceof RuntimeProfileStack ? $attributes['stack'] : RuntimeProfileStack::from($attributes['stack']),
            'config' => $attributes['config'] ?? [],
        ]);
    }
}
