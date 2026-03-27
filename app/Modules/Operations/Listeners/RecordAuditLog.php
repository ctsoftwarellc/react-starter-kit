<?php

namespace App\Modules\Operations\Listeners;

use App\Modules\AppPlatform\Events\ProjectCreated;
use App\Modules\AppPlatform\Events\ProjectDeleted;
use App\Modules\AppPlatform\Events\ProjectUpdated;
use App\Modules\Operations\Actions\RecordAuditLog as RecordAuditLogAction;

class RecordAuditLog
{
    private const EVENT_ACTION_MAP = [
        ProjectCreated::class => 'project.created',
        ProjectUpdated::class => 'project.updated',
        ProjectDeleted::class => 'project.deleted',
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
        return $event->project ?? null;
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

        return [null, null];
    }
}
