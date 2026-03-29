<?php

namespace App\Modules\AppPlatform\DTOs;

use App\Modules\AppPlatform\Enums\EnvironmentType;

class CreateEnvironmentData
{
    public function __construct(
        public string $clusterId,
        public string $name,
        public EnvironmentType $type,
        public bool $isAutoDeploy = false,
        public ?string $branch = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            clusterId: $data['cluster_id'],
            name: $data['name'],
            type: $data['type'] instanceof EnvironmentType ? $data['type'] : EnvironmentType::from($data['type']),
            isAutoDeploy: $data['is_auto_deploy'] ?? false,
            branch: $data['branch'] ?? null,
        );
    }
}
