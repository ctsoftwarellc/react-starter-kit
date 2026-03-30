<?php

namespace App\Modules\Networking\Services;

use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Networking\Models\Domain;

class DnsVerifier
{
    public function verify(Domain $domain): bool
    {
        $domain->loadMissing('environment.cluster.servers');

        $records = $this->resolveRecords($domain->hostname);

        if ($this->hasMatchingTxtToken($domain, $records['txt'])) {
            return true;
        }

        return $this->hasMatchingClusterAddress($domain, array_merge($records['a'], $records['aaaa']));
    }

    public function resolveRecords(string $hostname): array
    {
        $normalizedHost = strtolower(trim($hostname));

        $aRecords = $this->normalizeRecords(dns_get_record($normalizedHost, DNS_A) ?: [], 'ip');
        $aaaaRecords = $this->normalizeRecords(dns_get_record($normalizedHost, DNS_AAAA) ?: [], 'ipv6');
        $txtRecords = $this->normalizeTxtRecords(dns_get_record($normalizedHost, DNS_TXT) ?: []);

        return [
            'a' => $aRecords,
            'aaaa' => $aaaaRecords,
            'txt' => $txtRecords,
        ];
    }

    private function hasMatchingTxtToken(Domain $domain, array $txtRecords): bool
    {
        $token = trim((string) $domain->verification_token);

        if ($token === '') {
            return false;
        }

        foreach ($txtRecords as $record) {
            if ($record === $token || str_contains($record, $token)) {
                return true;
            }
        }

        return false;
    }

    private function hasMatchingClusterAddress(Domain $domain, array $resolvedAddresses): bool
    {
        $cluster = $domain->environment?->cluster;

        if ($cluster === null) {
            return false;
        }

        $webNodeAddresses = $cluster->servers
            ->filter(fn ($server) => $server->pivot?->role === NodeRole::Web->value && $server->pivot?->is_active)
            ->pluck('public_ip')
            ->filter()
            ->map(fn ($ip) => trim((string) $ip))
            ->unique()
            ->values()
            ->all();

        return count(array_intersect($resolvedAddresses, $webNodeAddresses)) > 0;
    }

    private function normalizeRecords(array $records, string $key): array
    {
        return collect($records)
            ->pluck($key)
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeTxtRecords(array $records): array
    {
        return collect($records)
            ->map(function (array $record): ?string {
                $value = $record['txt'] ?? $record['entries'][0] ?? null;

                return $value === null ? null : trim((string) $value, " \t\n\r\0\x0B\"");
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
