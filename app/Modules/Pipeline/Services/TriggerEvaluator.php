<?php

namespace App\Modules\Pipeline\Services;

use App\Modules\Pipeline\DTOs\WebhookEventData;
use App\Modules\Pipeline\Models\Pipeline;

class TriggerEvaluator
{
    public function matches(Pipeline $pipeline, WebhookEventData $event): bool
    {
        if (! $pipeline->is_active) {
            return false;
        }

        $events = array_values($pipeline->trigger_events ?? []);

        if (! in_array($event->triggerType->value, $events, true)) {
            return false;
        }

        if ($event->triggerType->value === 'tag') {
            return true;
        }

        $branches = array_values($pipeline->trigger_branches ?? []);

        if ($branches === [] || in_array('*', $branches, true)) {
            return true;
        }

        return $event->ref !== null && in_array($event->ref, $branches, true);
    }
}
