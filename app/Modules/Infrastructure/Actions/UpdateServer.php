<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\DTOs\UpdateServerData;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Facades\DB;

class UpdateServer
{
    public function execute(Server $server, UpdateServerData $data): Server
    {
        return DB::transaction(function () use ($server, $data) {
            $attributes = array_filter([
                'name' => $data->name,
                'hostname' => $data->hostname,
                'ssh_port' => $data->sshPort,
                'ssh_user' => $data->sshUser,
                'metadata' => $data->metadata,
            ], fn ($value) => $value !== null);

            $server->update($attributes);

            return $server;
        });
    }
}
