<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Server;
use InvalidArgumentException;

class DeleteServer
{
    public function execute(Server $server): void
    {
        $forbiddenStatuses = [
            ServerStatus::Active,
            ServerStatus::Bootstrapping,
        ];

        if (in_array($server->status, $forbiddenStatuses, true)) {
            throw new InvalidArgumentException(
                "Cannot delete server in [{$server->status->value}] status."
            );
        }

        $server->delete();
    }
}
