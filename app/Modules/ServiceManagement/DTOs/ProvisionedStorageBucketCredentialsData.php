<?php

namespace App\Modules\ServiceManagement\DTOs;

class ProvisionedStorageBucketCredentialsData
{
    public function __construct(
        public string $accessKey,
        public string $secretKey,
    ) {}
}
