<?php

namespace App\Modules\AppPlatform\DTOs;

use App\Modules\AppPlatform\Enums\Runtime;

class UpdateApplicationData
{
    public function __construct(
        public ?string $name = null,
        public ?Runtime $runtime = null,
        public ?string $repositoryUrl = null,
        public ?string $repositoryBranch = null,
        public ?string $gitConnectionId = null,
        public ?array $settings = null,
        public bool $hasName = false,
        public bool $hasRuntime = false,
        public bool $hasRepositoryUrl = false,
        public bool $hasRepositoryBranch = false,
        public bool $hasGitConnectionId = false,
        public bool $hasSettings = false,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            runtime: array_key_exists('runtime', $data)
                ? ($data['runtime'] instanceof Runtime ? $data['runtime'] : Runtime::from($data['runtime']))
                : null,
            repositoryUrl: $data['repository_url'] ?? null,
            repositoryBranch: $data['repository_branch'] ?? null,
            gitConnectionId: $data['git_connection_id'] ?? null,
            settings: $data['settings'] ?? null,
            hasName: array_key_exists('name', $data),
            hasRuntime: array_key_exists('runtime', $data),
            hasRepositoryUrl: array_key_exists('repository_url', $data),
            hasRepositoryBranch: array_key_exists('repository_branch', $data),
            hasGitConnectionId: array_key_exists('git_connection_id', $data),
            hasSettings: array_key_exists('settings', $data),
        );
    }
}
