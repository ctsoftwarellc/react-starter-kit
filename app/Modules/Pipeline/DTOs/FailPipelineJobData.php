<?php

namespace App\Modules\Pipeline\DTOs;

use App\Modules\Pipeline\Enums\PipelineJobStatus;

class FailPipelineJobData
{
    public function __construct(
        public PipelineJobStatus $status,
        public ?int $exitCode = null,
        public array $metadata = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            status: $data['status'] instanceof PipelineJobStatus
                ? $data['status']
                : PipelineJobStatus::from($data['status']),
            exitCode: isset($data['exit_code']) ? (int) $data['exit_code'] : null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
