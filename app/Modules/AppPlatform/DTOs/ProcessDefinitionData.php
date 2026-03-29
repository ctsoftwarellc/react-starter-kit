<?php

namespace App\Modules\AppPlatform\DTOs;

use App\Modules\AppPlatform\Enums\ProcessType;

class ProcessDefinitionData
{
    public function __construct(
        public ProcessType $type,
        public string $command,
        public int $instances = 1,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            type: $data['type'] instanceof ProcessType ? $data['type'] : ProcessType::from($data['type']),
            command: $data['command'],
            instances: $data['instances'] ?? 1,
        );
    }
}
