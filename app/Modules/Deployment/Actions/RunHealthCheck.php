<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Deployment\Enums\HealthCheckType;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Deployment\Models\HealthCheck;
use Illuminate\Support\Facades\Http;

class RunHealthCheck
{
    public function execute(Deployment $deployment, DeploymentStep $step, ?HealthCheck $healthCheck = null): bool
    {
        $deployment->loadMissing('environment.healthChecks');
        $step->forceFill([
            'status' => DeploymentStepStatus::HealthChecking,
        ])->save();

        $healthCheck ??= $deployment->environment->healthChecks
            ->firstWhere('is_active', true);

        if ($healthCheck === null) {
            $step->forceFill([
                'status' => DeploymentStepStatus::Active,
                'finished_at' => now(),
            ])->save();

            return true;
        }

        $this->pause(5);

        if ($healthCheck->type !== HealthCheckType::Http) {
            $step->forceFill([
                'status' => DeploymentStepStatus::Active,
                'finished_at' => now(),
            ])->save();

            return true;
        }

        $successfulChecks = 0;
        $consecutiveFailures = 0;

        while ($successfulChecks < $healthCheck->healthy_threshold) {
            try {
                $response = Http::timeout($healthCheck->timeout_seconds)->get($healthCheck->target);

                if ($response->successful()) {
                    $successfulChecks++;
                    $consecutiveFailures = 0;
                } else {
                    $consecutiveFailures++;
                }
            } catch (\Throwable) {
                $consecutiveFailures++;
            }

            if ($consecutiveFailures >= $healthCheck->unhealthy_threshold) {
                $step->forceFill([
                    'status' => DeploymentStepStatus::Failed,
                    'finished_at' => now(),
                ])->save();

                return false;
            }

            if ($successfulChecks < $healthCheck->healthy_threshold) {
                $this->pause($healthCheck->interval_seconds);
            }
        }

        $step->forceFill([
            'status' => DeploymentStepStatus::Active,
            'finished_at' => now(),
        ])->save();

        return true;
    }

    private function pause(int $seconds): void
    {
        sleep(max(0, $seconds));
    }
}
