<?php

namespace App\Modules\Infrastructure\Events;

use App\Modules\Infrastructure\Models\Server;

class ServerBootstrapped
{
    public function __construct(public Server $server) {}
}
