<?php

namespace App\Modules\Infrastructure\Services;

use App\Modules\Infrastructure\Models\Server;

class ServerBootstrapper
{
    public function bootstrap(Server $server): void
    {
        $ssh = new SshService;

        $ssh->connect(
            $server->public_ip,
            $server->ssh_port,
            $server->ssh_user,
        );

        $ssh->execute('apt-get update -qq');
        $ssh->execute('apt-get install -y -qq curl wget unzip');

        $ssh->execute('ufw allow 22/tcp');
        $ssh->execute('ufw allow 80/tcp');
        $ssh->execute('ufw allow 443/tcp');
        $ssh->execute('ufw --force enable');

        $ssh->disconnect();
    }
}
