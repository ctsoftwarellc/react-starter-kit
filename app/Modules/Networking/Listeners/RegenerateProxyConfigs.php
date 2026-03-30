<?php

namespace App\Modules\Networking\Listeners;

use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Networking\Events\DomainVerified;
use App\Modules\Networking\Jobs\PushProxyConfig;

class RegenerateProxyConfigs
{
    public function handle(object $event): void
    {
        $cluster = match (true) {
            $event instanceof DomainVerified => $event->domain->environment?->cluster,
            $event instanceof ClusterTopologyChanged => $event->cluster,
            default => null,
        };

        if ($cluster === null) {
            return;
        }

        PushProxyConfig::dispatch($cluster)->afterCommit();
    }
}
