<?php

namespace App\Modules\ServiceManagement\DTOs;

class ProvisionStorageBucketData
{
    public function __construct(
        public string $name,
        public string $provider,
        public string $region,
        public string $bucketName,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            name: $data['name'],
            provider: $data['provider'],
            region: $data['region'],
            bucketName: $data['bucket_name'],
        );
    }
}
