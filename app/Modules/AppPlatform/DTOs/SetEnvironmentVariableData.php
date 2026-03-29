<?php

namespace App\Modules\AppPlatform\DTOs;

class SetEnvironmentVariableData
{
    public function __construct(
        public string $key,
        public string $value,
        public bool $isBuildArg = false,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            key: $data['key'],
            value: $data['value'],
            isBuildArg: $data['is_build_arg'] ?? false,
        );
    }
}
