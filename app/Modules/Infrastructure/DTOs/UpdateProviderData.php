<?php

namespace App\Modules\Infrastructure\DTOs;

class UpdateProviderData
{
    public function __construct(
        public ?string $name = null,
        public ?array $credentials = null,
        public ?bool $isActive = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            credentials: $data['credentials'] ?? null,
            isActive: $data['is_active'] ?? null,
        );
    }
}
