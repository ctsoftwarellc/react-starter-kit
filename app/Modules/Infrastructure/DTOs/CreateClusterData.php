<?php

namespace App\Modules\Infrastructure\DTOs;

class CreateClusterData
{
    public function __construct(
        public string $name,
        public ?array $settings = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            settings: $data['settings'] ?? null,
        );
    }
}
