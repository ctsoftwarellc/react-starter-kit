<?php

namespace App\Modules\Networking\Services;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Models\Cluster;

class CaddyConfigGenerator
{
    public function generateForCluster(Cluster $cluster): string
    {
        $cluster->loadMissing([
            'environments.runtimeProfile',
            'environments.activeRelease',
            'environments.processDefinitions',
            'environments.domains.certificate',
        ]);

        $blocks = $cluster->environments
            ->map(fn (Environment $environment) => $this->generateForEnvironment($environment))
            ->filter()
            ->implode("\n\n");

        if ($blocks === '') {
            return "{\n    auto_https off\n}\n";
        }

        return $blocks."\n";
    }

    public function generateForEnvironment(Environment $environment): string
    {
        $environment->loadMissing(['runtimeProfile', 'activeRelease', 'processDefinitions', 'domains.certificate']);

        $verifiedDomains = $environment->domains
            ->filter(fn ($domain) => $domain->is_verified)
            ->sortByDesc(fn ($domain) => $domain->is_primary)
            ->values();

        if ($verifiedDomains->isEmpty()) {
            return '';
        }

        $upstream = $this->resolveUpstream($environment);

        return $verifiedDomains
            ->map(function ($domain) use ($upstream): string {
                return implode("\n", [
                    sprintf('%s {', $domain->hostname),
                    '    encode zstd gzip',
                    sprintf('    reverse_proxy %s', $upstream),
                    '}',
                ]);
            })
            ->implode("\n\n");
    }

    private function resolveUpstream(Environment $environment): string
    {
        $runtimeConfig = is_array($environment->runtimeProfile?->config) ? $environment->runtimeProfile->config : [];
        $releaseSnapshot = is_array($environment->activeRelease?->config_snapshot) ? $environment->activeRelease->config_snapshot : [];

        $port = $runtimeConfig['web_port']
            ?? $runtimeConfig['port']
            ?? data_get($releaseSnapshot, 'runtime.web_port')
            ?? data_get($releaseSnapshot, 'runtime.port')
            ?? 8000;

        return sprintf('127.0.0.1:%s', $port);
    }
}
