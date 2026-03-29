<?php

namespace App\Modules\ServiceManagement\DTOs;

class ProvisionedDatabaseConnectionData
{
    public function __construct(
        public string $host,
        public int $port,
        public string $databaseName,
        public string $username,
        public string $password,
    ) {}
}
