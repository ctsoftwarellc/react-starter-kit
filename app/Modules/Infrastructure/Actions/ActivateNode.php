<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Models\Server;

class ActivateNode
{
    public function execute(Server $server): Server
    {
        $previousStatus = $server->status->value;

        $server->transitionTo(ServerStatus::Active);

        event(new ServerHealthChanged($server, $previousStatus, ServerStatus::Active->value));

        return $server;
    }
}
