<?php

namespace App\Modules\ServiceManagement\DTOs;

use App\Modules\ServiceManagement\Enums\DatabaseEngine;

class ProvisionDatabaseData
{
    public function __construct(
        public string $clusterId,
        public string $name,
        public DatabaseEngine $engine,
        public ?string $version = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            clusterId: $data['cluster_id'],
            name: $data['name'],
            engine: $data['engine'] instanceof DatabaseEngine ? $data['engine'] : DatabaseEngine::from($data['engine']),
            version: $data['version'] ?? null,
        );
    }
}
