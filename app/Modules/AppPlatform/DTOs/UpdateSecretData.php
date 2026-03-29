<?php

namespace App\Modules\AppPlatform\DTOs;

class UpdateSecretData
{
    public function __construct(
        public ?string $key = null,
        public ?string $value = null,
        public bool $hasKey = false,
        public bool $hasValue = false,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            key: $data['key'] ?? null,
            value: $data['value'] ?? null,
            hasKey: array_key_exists('key', $data),
            hasValue: array_key_exists('value', $data),
        );
    }
}
