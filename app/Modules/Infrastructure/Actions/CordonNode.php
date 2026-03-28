<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Models\Server;

class CordonNode
{
    public function execute(Server $server): Server
    {
        $previousStatus = $server->status->value;

        $server->transitionTo(ServerStatus::Cordoned);

        event(new ServerHealthChanged($server, $previousStatus, ServerStatus::Cordoned->value));

        return $server;
    }
}
