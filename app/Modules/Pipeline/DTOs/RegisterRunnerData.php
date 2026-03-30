<?php

namespace App\Modules\Pipeline\DTOs;

class RegisterRunnerData
{
    public function __construct(
        public string $name,
        public ?string $platform = null,
        public array $metadata = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            platform: $data['platform'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
