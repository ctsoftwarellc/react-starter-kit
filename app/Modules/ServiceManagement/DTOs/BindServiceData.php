<?php

namespace App\Modules\ServiceManagement\DTOs;

use App\Modules\ServiceManagement\Enums\ServiceType;

class BindServiceData
{
    public function __construct(
        public ServiceType $serviceType,
        public string $bindingName,
        public ?string $databaseInstanceId = null,
        public ?string $cacheInstanceId = null,
        public ?string $storageBucketId = null,
        public array $config = [],
    ) {}

    public static function from(array $data): self
    {
        return new self(
            serviceType: $data['service_type'] instanceof ServiceType ? $data['service_type'] : ServiceType::from($data['service_type']),
            bindingName: $data['binding_name'],
            databaseInstanceId: $data['database_instance_id'] ?? null,
            cacheInstanceId: $data['cache_instance_id'] ?? null,
            storageBucketId: $data['storage_bucket_id'] ?? null,
            config: $data['config'] ?? [],
        );
    }
}
