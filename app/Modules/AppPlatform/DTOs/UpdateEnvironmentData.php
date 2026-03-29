<?php

namespace App\Modules\AppPlatform\DTOs;

use App\Modules\AppPlatform\Enums\EnvironmentType;

class UpdateEnvironmentData
{
    public function __construct(
        public ?string $clusterId = null,
        public ?string $name = null,
        public ?EnvironmentType $type = null,
        public ?bool $isAutoDeploy = null,
        public ?string $branch = null,
        public bool $hasClusterId = false,
        public bool $hasName = false,
        public bool $hasType = false,
        public bool $hasIsAutoDeploy = false,
        public bool $hasBranch = false,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            clusterId: $data['cluster_id'] ?? null,
            name: $data['name'] ?? null,
            type: array_key_exists('type', $data)
                ? ($data['type'] instanceof EnvironmentType ? $data['type'] : EnvironmentType::from($data['type']))
                : null,
            isAutoDeploy: $data['is_auto_deploy'] ?? null,
            branch: $data['branch'] ?? null,
            hasClusterId: array_key_exists('cluster_id', $data),
            hasName: array_key_exists('name', $data),
            hasType: array_key_exists('type', $data),
            hasIsAutoDeploy: array_key_exists('is_auto_deploy', $data),
            hasBranch: array_key_exists('branch', $data),
        );
    }
}
