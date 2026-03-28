<?php

namespace App\Modules\Infrastructure\Events;

use App\Modules\Infrastructure\Models\Server;

class ServerRegistered
{
    public function __construct(public Server $server) {}
}
