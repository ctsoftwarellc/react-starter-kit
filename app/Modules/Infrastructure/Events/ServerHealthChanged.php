<?php

namespace App\Modules\Infrastructure\Events;

use App\Modules\Infrastructure\Models\Server;

class ServerHealthChanged
{
    public function __construct(
        public Server $server,
        public string $previousStatus,
        public string $newStatus,
    ) {}
}
