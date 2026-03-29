<?php

namespace App\Modules\ServiceManagement\DTOs;

use App\Modules\ServiceManagement\Enums\CacheEngine;

class ProvisionCacheData
{
    public function __construct(
        public string $clusterId,
        public string $name,
        public CacheEngine $engine,
        public ?string $version = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            clusterId: $data['cluster_id'],
            name: $data['name'],
            engine: $data['engine'] instanceof CacheEngine ? $data['engine'] : CacheEngine::from($data['engine']),
            version: $data['version'] ?? null,
        );
    }
}
