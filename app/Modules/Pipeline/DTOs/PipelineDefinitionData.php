<?php

namespace App\Modules\Pipeline\DTOs;

class PipelineDefinitionData
{
    public function __construct(
        public string $name,
        public array $definition,
        public bool $isActive = true,
        public array $triggerBranches = ['main'],
        public array $triggerEvents = ['push'],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            definition: $data['definition'],
            isActive: (bool) ($data['is_active'] ?? true),
            triggerBranches: array_values($data['trigger_branches'] ?? ['main']),
            triggerEvents: array_values($data['trigger_events'] ?? ['push']),
        );
    }
}
