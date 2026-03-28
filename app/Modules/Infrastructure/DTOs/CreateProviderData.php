<?php

namespace App\Modules\Infrastructure\DTOs;

use App\Modules\Infrastructure\Enums\ProviderType;

class CreateProviderData
{
    public function __construct(
        public string $name,
        public ProviderType $type,
        public array $credentials,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            type: ProviderType::from($data['type']),
            credentials: $data['credentials'],
        );
    }
}
