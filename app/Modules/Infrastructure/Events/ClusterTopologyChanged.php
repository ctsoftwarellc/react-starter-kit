<?php

namespace App\Modules\Infrastructure\Events;

use App\Modules\Infrastructure\Models\Cluster;

class ClusterTopologyChanged
{
    public function __construct(
        public Cluster $cluster,
        public string $changeType,
    ) {}
}
