<?php

namespace App\Modules\Operations\Listeners;

use App\Modules\AppPlatform\Events\ProjectCreated;
use App\Modules\AppPlatform\Events\ProjectDeleted;
use App\Modules\AppPlatform\Events\ProjectUpdated;
use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Infrastructure\Events\ProviderCreated;
use App\Modules\Infrastructure\Events\ProviderDeleted;
use App\Modules\Infrastructure\Events\ProviderUpdated;
use App\Modules\Infrastructure\Events\ServerBootstrapped;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Events\ServerRegistered;
use App\Modules\Operations\Actions\RecordAuditLog as RecordAuditLogAction;

class RecordAuditLog
{
    private const EVENT_ACTION_MAP = [
        ProjectCreated::class => 'project.created',
        ProjectUpdated::class => 'project.updated',
        ProjectDeleted::class => 'project.deleted',
        ProviderCreated::class => 'provider.created',
        ProviderUpdated::class => 'provider.updated',
        ProviderDeleted::class => 'provider.deleted',
        ServerRegistered::class => 'server.registered',
        ServerBootstrapped::class => 'server.bootstrapped',
        ServerHealthChanged::class => 'server.health_changed',
        ClusterTopologyChanged::class => 'cluster.topology_changed',
    ];

    public function handle(object $event): void
    {
        $action = self::EVENT_ACTION_MAP[$event::class] ?? null;

        if ($action === null) {
            return;
        }

        $auditable = $this->getAuditable($event);
        [$oldValues, $newValues] = $this->getValues($event);

        (new RecordAuditLogAction)->execute(
            action: $action,
            auditable: $auditable,
            oldValues: $oldValues,
            newValues: $newValues,
        );
    }

    private function getAuditable(object $event): mixed
    {
        return $event->project
            ?? $event->provider
            ?? $event->server
            ?? $event->cluster
            ?? null;
    }

    private function getValues(object $event): array
    {
        if ($event instanceof ProjectUpdated) {
            return [$event->oldValues, $event->newValues];
        }

        if ($event instanceof ProjectCreated) {
            return [null, [
                'name' => $event->project->name,
                'slug' => $event->project->slug,
                'description' => $event->project->description,
            ]];
        }

        if ($event instanceof ProjectDeleted) {
            return [[
                'name' => $event->project->name,
                'slug' => $event->project->slug,
            ], null];
        }

        if ($event instanceof ProviderCreated) {
            return [null, [
                'name' => $event->provider->name,
                'type' => $event->provider->type->value,
            ]];
        }

        if ($event instanceof ProviderUpdated) {
            return [null, [
                'name' => $event->provider->name,
            ]];
        }

        if ($event instanceof ProviderDeleted) {
            return [[
                'name' => $event->provider->name,
                'type' => $event->provider->type->value,
            ], null];
        }

        if ($event instanceof ServerRegistered) {
            return [null, [
                'name' => $event->server->name,
                'hostname' => $event->server->hostname,
                'public_ip' => $event->server->public_ip,
            ]];
        }

        if ($event instanceof ServerBootstrapped) {
            return [null, [
                'name' => $event->server->name,
                'status' => $event->server->status->value,
            ]];
        }

        if ($event instanceof ServerHealthChanged) {
            return [
                ['status' => $event->previousStatus],
                ['status' => $event->newStatus],
            ];
        }

        if ($event instanceof ClusterTopologyChanged) {
            return [null, [
                'change_type' => $event->changeType,
            ]];
        }

        return [null, null];
    }
}
