<?php

namespace App\Modules\ServiceManagement\DTOs;

class ProvisionedCacheConnectionData
{
    public function __construct(
        public string $host,
        public int $port,
        public ?string $password,
    ) {}
}
