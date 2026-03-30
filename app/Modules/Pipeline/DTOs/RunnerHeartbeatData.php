<?php

namespace App\Modules\Pipeline\DTOs;

class RunnerHeartbeatData
{
    public function __construct(
        public ?string $platform = null,
        public array $metadata = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            platform: $data['platform'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
