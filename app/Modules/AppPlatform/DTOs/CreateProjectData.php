<?php

namespace App\Modules\AppPlatform\DTOs;

class CreateProjectData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
        );
    }
}
