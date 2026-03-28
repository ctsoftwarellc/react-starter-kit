<?php

namespace App\Modules\Infrastructure\Services\Providers;

interface ProviderInterface
{
    public function testConnection(): bool;

    public function listRegions(): array;

    public function listSizes(): array;

    public function createServer(array $config): array;

    public function deleteServer(string $externalId): bool;
}
