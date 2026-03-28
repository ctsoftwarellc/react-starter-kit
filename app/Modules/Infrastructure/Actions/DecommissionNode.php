<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Models\Server;

class DecommissionNode
{
    public function execute(Server $server): Server
    {
        $previousStatus = $server->status->value;

        $server->transitionTo(ServerStatus::Decommissioning);

        event(new ServerHealthChanged($server, $previousStatus, ServerStatus::Decommissioning->value));

        return $server;
    }
}
