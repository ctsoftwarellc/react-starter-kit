<?php

namespace App\Modules\Infrastructure\Actions;

use App\Modules\Infrastructure\Models\Server;
use App\Modules\Infrastructure\Services\SshService;

class RunServerHardeningAudit
{
    public function __construct(
        private readonly SshService $ssh = new SshService,
    ) {}

    public function execute(Server $server): array
    {
        $this->ssh->connect($server->public_ip, $server->ssh_port, $server->ssh_user);

        $checks = [
            'helm_user' => 'id -u helm >/dev/null 2>&1 && printf ok || printf missing',
            'ssh_password_auth_disabled' => 'grep -Eq "^PasswordAuthentication no" /etc/ssh/sshd_config && printf ok || printf drift',
            'ssh_root_login_disabled' => 'grep -Eq "^PermitRootLogin no" /etc/ssh/sshd_config && printf ok || printf drift',
            'fail2ban_enabled' => 'systemctl is-enabled fail2ban >/dev/null 2>&1 && printf ok || printf drift',
            'unattended_upgrades_enabled' => 'systemctl is-enabled unattended-upgrades >/dev/null 2>&1 && printf ok || printf drift',
            'agent_config_permissions' => 'test -f /etc/helm/agent.conf && stat -c %a /etc/helm/agent.conf | grep -qx 600 && printf ok || printf drift',
            'ufw_enabled' => 'ufw status | grep -q "Status: active" && printf ok || printf drift',
        ];

        $results = [];

        foreach ($checks as $name => $command) {
            $results[$name] = trim($this->ssh->execute($command));
        }

        $this->ssh->disconnect();

        return $results;
    }
}
