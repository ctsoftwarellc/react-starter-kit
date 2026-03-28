<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\DTOs\RegisterServerData;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerRegistered;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Facades\DB;

class RegisterServer
{
    public function execute(RegisterServerData $data): Server
    {
        return DB::transaction(function () use ($data) {
            $server = Server::create([
                'name' => $data->name,
                'hostname' => $data->hostname,
                'public_ip' => $data->publicIp,
                'private_ip' => $data->privateIp,
                'ssh_port' => $data->sshPort,
                'ssh_user' => $data->sshUser,
                'provider_id' => $data->providerId,
                'os' => $data->os,
                'region' => $data->region,
                'status' => ServerStatus::Pending,
            ]);

            event(new ServerRegistered($server));

            return $server;
        });
    }
}
