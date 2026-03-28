<?php

namespace App\Modules\Infrastructure\DTOs;

class UpdateServerData
{
    public function __construct(
        public ?string $name = null,
        public ?string $hostname = null,
        public ?int $sshPort = null,
        public ?string $sshUser = null,
        public ?array $metadata = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            hostname: $data['hostname'] ?? null,
            sshPort: $data['ssh_port'] ?? null,
            sshUser: $data['ssh_user'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
