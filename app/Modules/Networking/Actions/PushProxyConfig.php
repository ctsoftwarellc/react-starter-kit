<?php

namespace App\Modules\Networking\Actions;

use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Networking\Services\CaddyConfigGenerator;
use App\Modules\Networking\Services\ProxyConfigPusher;
use Illuminate\Support\Collection;

class PushProxyConfig
{
    public function __construct(
        private readonly CaddyConfigGenerator $generator = new CaddyConfigGenerator,
        private readonly ProxyConfigPusher $pusher = new ProxyConfigPusher,
    ) {}

    public function execute(Cluster $cluster): Collection
    {
        $config = $this->generator->generateForCluster($cluster);

        return $this->pusher->push($cluster, $config);
    }
}
