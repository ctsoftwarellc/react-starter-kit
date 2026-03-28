<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Events\ServerBootstrapped;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Infrastructure\Services\ServerBootstrapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BootstrapServer
{
    public function execute(Server $server): Server
    {
        return DB::transaction(function () use ($server) {
            $agentToken = Str::random(64);

            $server->update([
                'agent_token' => $agentToken,
                'agent_token_hash' => hash('sha256', $agentToken),
            ]);

            $server->transitionTo(ServerStatus::Bootstrapping);

            (new ServerBootstrapper)->bootstrap($server);

            $server->transitionTo(ServerStatus::Active);

            event(new ServerBootstrapped($server));

            return $server;
        });
    }
}
