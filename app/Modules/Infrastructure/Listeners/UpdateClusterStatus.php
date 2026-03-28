<?php

namespace App\Modules\Infrastructure\Listeners;

use App\Modules\Infrastructure\Enums\ClusterStatus;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerBootstrapped;
use App\Modules\Infrastructure\Events\ServerHealthChanged;

class UpdateClusterStatus
{
    public function handle(object $event): void
    {
        if ($event instanceof ServerBootstrapped) {
            $this->handleServerBootstrapped($event);
        }

        if ($event instanceof ServerHealthChanged) {
            $this->handleServerHealthChanged($event);
        }
    }

    private function handleServerBootstrapped(ServerBootstrapped $event): void
    {
        $clusters = $event->server->clusters;

        foreach ($clusters as $cluster) {
            if ($cluster->status === ClusterStatus::Provisioning) {
                $allActive = $cluster->servers()
                    ->wherePivot('is_active', true)
                    ->get()
                    ->every(fn ($server) => $server->status === ServerStatus::Active);

                if ($allActive) {
                    $cluster->transitionTo(ClusterStatus::Active);
                }
            }
        }
    }

    private function handleServerHealthChanged(ServerHealthChanged $event): void
    {
        $clusters = $event->server->clusters;

        foreach ($clusters as $cluster) {
            if ($cluster->status === ClusterStatus::Active && in_array($event->newStatus, [
                ServerStatus::Failed->value,
                ServerStatus::Draining->value,
            ])) {
                $cluster->transitionTo(ClusterStatus::Degraded);
            }
        }
    }
}
