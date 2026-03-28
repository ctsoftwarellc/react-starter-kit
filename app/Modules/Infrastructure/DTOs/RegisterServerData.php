<?php

namespace App\Modules\Infrastructure\DTOs;

class RegisterServerData
{
    public function __construct(
        public string $name,
        public string $hostname,
        public string $publicIp,
        public ?string $privateIp = null,
        public int $sshPort = 22,
        public string $sshUser = 'root',
        public ?string $providerId = null,
        public ?string $os = null,
        public ?string $region = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            hostname: $data['hostname'],
            publicIp: $data['public_ip'],
            privateIp: $data['private_ip'] ?? null,
            sshPort: $data['ssh_port'] ?? 22,
            sshUser: $data['ssh_user'] ?? 'root',
            providerId: $data['provider_id'] ?? null,
            os: $data['os'] ?? null,
            region: $data['region'] ?? null,
        );
    }
}
