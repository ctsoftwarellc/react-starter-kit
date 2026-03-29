<?php

namespace App\Modules\AppPlatform\DTOs;

class CreateSecretData
{
    public function __construct(
        public string $key,
        public string $value,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            key: $data['key'],
            value: $data['value'],
        );
    }
}
