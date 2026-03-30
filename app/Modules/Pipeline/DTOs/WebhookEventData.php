<?php

namespace App\Modules\Pipeline\DTOs;

use App\Modules\Pipeline\Enums\TriggerType;

class WebhookEventData
{
    public function __construct(
        public string $provider,
        public TriggerType $triggerType,
        public ?string $ref,
        public ?string $sha,
        public ?string $actor,
        public ?string $eventName,
        public array $payload = [],
    ) {}
}
