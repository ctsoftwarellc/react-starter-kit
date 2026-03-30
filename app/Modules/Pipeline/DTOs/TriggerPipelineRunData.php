<?php

namespace App\Modules\Pipeline\DTOs;

use App\Modules\Pipeline\Enums\TriggerType;

class TriggerPipelineRunData
{
    public function __construct(
        public TriggerType $triggerType,
        public ?string $environmentId = null,
        public ?string $triggerRef = null,
        public ?string $triggerSha = null,
        public ?string $triggerActor = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            triggerType: $data['trigger_type'] instanceof TriggerType
                ? $data['trigger_type']
                : TriggerType::from($data['trigger_type']),
            environmentId: $data['environment_id'] ?? null,
            triggerRef: $data['trigger_ref'] ?? null,
            triggerSha: $data['trigger_sha'] ?? null,
            triggerActor: $data['trigger_actor'] ?? null,
        );
    }
}
