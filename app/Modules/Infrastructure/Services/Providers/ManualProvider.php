<?php

namespace App\Modules\Infrastructure\Services\Providers;

class ManualProvider implements ProviderInterface
{
    public function __construct(array $credentials = []) {}

    public function testConnection(): bool
    {
        return true;
    }

    public function listRegions(): array
    {
        return [];
    }

    public function listSizes(): array
    {
        return [];
    }

    public function createServer(array $config): array
    {
        return [];
    }

    public function deleteServer(string $externalId): bool
    {
        return true;
    }
}
