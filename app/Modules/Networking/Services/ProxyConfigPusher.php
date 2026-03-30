<?php

namespace App\Modules\Networking\Services;

use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Cluster;
use Illuminate\Support\Collection;

class ProxyConfigPusher
{
    public function push(Cluster $cluster, string $config): Collection
    {
        $cluster->loadMissing('servers');

        return $cluster->servers
            ->filter(fn ($server) => $server->pivot?->role === NodeRole::Web->value && $server->pivot?->is_active)
            ->values()
            ->map(function ($server) use ($cluster, $config) {
                return AgentCommand::create([
                    'server_id' => $server->id,
                    'type' => AgentCommandType::UpdateProxyConfig,
                    'payload' => [
                        'cluster_id' => $cluster->id,
                        'config' => $config,
                    ],
                    'status' => AgentCommandStatus::Pending,
                    'expires_at' => now()->addMinutes(10),
                ]);
            });
    }
}
