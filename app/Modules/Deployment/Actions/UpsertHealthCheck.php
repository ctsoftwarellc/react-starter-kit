<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\HealthCheck;
use Illuminate\Support\Arr;

class UpsertHealthCheck
{
    public function execute(Environment $environment, array $attributes): HealthCheck
    {
        $healthCheck = isset($attributes['id'])
            ? $environment->healthChecks()->firstWhere('id', $attributes['id'])
            : $environment->healthChecks()->latest('created_at')->first();

        $payload = Arr::except($attributes, ['id']);

        if ($healthCheck === null) {
            return $environment->healthChecks()->create($payload);
        }

        $healthCheck->fill($payload)->save();

        return $healthCheck->fresh();
    }
}
