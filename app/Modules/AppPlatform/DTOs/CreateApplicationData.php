<?php

namespace App\Modules\AppPlatform\DTOs;

use App\Modules\AppPlatform\Enums\Runtime;

class CreateApplicationData
{
    public function __construct(
        public string $name,
        public Runtime $runtime,
        public ?string $repositoryUrl = null,
        public string $repositoryBranch = 'main',
        public ?string $gitConnectionId = null,
        public array $settings = [],
        public ?string $defaultClusterId = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            runtime: $data['runtime'] instanceof Runtime ? $data['runtime'] : Runtime::from($data['runtime'] ?? Runtime::Php->value),
            repositoryUrl: $data['repository_url'] ?? null,
            repositoryBranch: $data['repository_branch'] ?? 'main',
            gitConnectionId: $data['git_connection_id'] ?? null,
            settings: $data['settings'] ?? [],
            defaultClusterId: $data['default_cluster_id'] ?? null,
        );
    }
}
