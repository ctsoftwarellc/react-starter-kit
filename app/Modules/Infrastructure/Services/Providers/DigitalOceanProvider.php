<?php

namespace App\Modules\Infrastructure\Services\Providers;

use Illuminate\Support\Facades\Http;

class DigitalOceanProvider implements ProviderInterface
{
    private string $apiToken;

    private string $baseUrl = 'https://api.digitalocean.com/v2';

    public function __construct(array $credentials)
    {
        $this->apiToken = $credentials['api_key'] ?? '';
    }

    public function testConnection(): bool
    {
        $response = Http::withToken($this->apiToken)
            ->get("{$this->baseUrl}/account");

        return $response->successful();
    }

    public function listRegions(): array
    {
        $response = Http::withToken($this->apiToken)
            ->get("{$this->baseUrl}/regions");

        if (! $response->successful()) {
            return [];
        }

        return $response->json('regions', []);
    }

    public function listSizes(): array
    {
        $response = Http::withToken($this->apiToken)
            ->get("{$this->baseUrl}/sizes");

        if (! $response->successful()) {
            return [];
        }

        return $response->json('sizes', []);
    }

    public function createServer(array $config): array
    {
        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/droplets", [
                'name' => $config['name'],
                'region' => $config['region'] ?? 'nyc3',
                'size' => $config['size'] ?? 's-1vcpu-1gb',
                'image' => $config['image'] ?? 'ubuntu-24-04-x64',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to create DigitalOcean droplet: '.$response->body());
        }

        $droplet = $response->json('droplet', []);

        return [
            'external_id' => (string) ($droplet['id'] ?? ''),
            'public_ip' => $droplet['networks']['v4'][0]['ip_address'] ?? null,
            'private_ip' => $droplet['networks']['v4'][1]['ip_address'] ?? null,
            'os' => $droplet['image']['distribution'] ?? null,
            'cpu_cores' => $droplet['vcpus'] ?? null,
            'memory_mb' => $droplet['memory'] ?? null,
            'disk_gb' => $droplet['disk'] ?? null,
        ];
    }

    public function deleteServer(string $externalId): bool
    {
        $response = Http::withToken($this->apiToken)
            ->delete("{$this->baseUrl}/droplets/{$externalId}");

        return $response->successful();
    }
}
