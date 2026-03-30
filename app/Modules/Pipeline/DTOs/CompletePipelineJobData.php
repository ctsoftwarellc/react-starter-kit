<?php

namespace App\Modules\Pipeline\DTOs;

class CompletePipelineJobData
{
    public function __construct(
        public int $exitCode = 0,
        public array $metadata = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            exitCode: (int) ($data['exit_code'] ?? 0),
            metadata: $data['metadata'] ?? [],
        );
    }
}
