<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Models\Server;

class DrainNode
{
    public function execute(Server $server): Server
    {
        $previousStatus = $server->status->value;

        $server->transitionTo(ServerStatus::Draining);

        event(new ServerHealthChanged($server, $previousStatus, ServerStatus::Draining->value));

        return $server;
    }
}
