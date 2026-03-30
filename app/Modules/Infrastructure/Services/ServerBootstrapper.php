<?php

namespace App\Modules\Infrastructure\Services;

use App\Modules\Infrastructure\Enums\NodeRole;
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

        foreach ($this->commandsFor($server) as $command) {
            $ssh->execute($command);
        }

        $ssh->disconnect();
    }

    private function commandsFor(Server $server): array
    {
        $publicPorts = $this->publicPortsFor($server);
        $agentToken = (string) $server->agent_token;
        $agentConfig = sprintf(
            "server_id=%s\nagent_token=%s\ncontrol_plane_url=%s\n",
            $server->id,
            $agentToken,
            rtrim((string) config('app.url'), '/'),
        );
        $escapedConfig = escapeshellarg($agentConfig);
        $privateIp = $server->private_ip ?: '127.0.0.1';

        $commands = [
            'export DEBIAN_FRONTEND=noninteractive',
            'apt-get update -qq',
            'apt-get install -y -qq curl wget unzip ufw fail2ban unattended-upgrades ca-certificates',
            'id -u helm >/dev/null 2>&1 || useradd --create-home --shell /bin/bash --user-group helm',
            'install -d -m 700 -o helm -g helm /home/helm/.ssh',
            'if [ -f /root/.ssh/authorized_keys ]; then cp /root/.ssh/authorized_keys /home/helm/.ssh/authorized_keys && chown helm:helm /home/helm/.ssh/authorized_keys && chmod 600 /home/helm/.ssh/authorized_keys; fi',
            'printf "helm ALL=(ALL) NOPASSWD:/usr/bin/systemctl,/usr/bin/journalctl,/usr/sbin/ufw,/usr/bin/tee,/usr/bin/tail,/usr/bin/head,/usr/bin/cat,/usr/bin/chown,/usr/bin/chmod,/usr/bin/mkdir,/usr/bin/rm,/usr/bin/cp,/usr/bin/mv,/usr/bin/tar,/usr/bin/rsync\n" >/etc/sudoers.d/90-helm && chmod 440 /etc/sudoers.d/90-helm',
            'install -d -m 755 /etc/helm',
            sprintf('printf %s >/etc/helm/agent.conf', $escapedConfig),
            'chmod 600 /etc/helm/agent.conf',
            'printf "[sshd]\nenabled = true\nmaxretry = 5\n" >/etc/fail2ban/jail.d/helm-sshd.local',
            'systemctl enable fail2ban --now',
            'printf "APT::Periodic::Update-Package-Lists \"1\";\nAPT::Periodic::Unattended-Upgrade \"1\";\n" >/etc/apt/apt.conf.d/20auto-upgrades',
            'systemctl enable unattended-upgrades --now',
            'ufw --force reset',
            'ufw default deny incoming',
            'ufw default allow outgoing',
            'ufw allow 22/tcp',
            'if [ -f /etc/ssh/sshd_config ]; then sed -i "s/^#\?PasswordAuthentication .*/PasswordAuthentication no/" /etc/ssh/sshd_config; fi',
            'if [ -f /etc/ssh/sshd_config ]; then sed -i "s/^#\?PermitRootLogin .*/PermitRootLogin no/" /etc/ssh/sshd_config; fi',
            'systemctl reload ssh || systemctl reload sshd || true',
        ];

        foreach ($publicPorts as $port) {
            $commands[] = sprintf('ufw allow %d/tcp', $port);
        }

        $commands[] = 'ufw --force enable';
        $commands[] = sprintf("if [ -f /etc/mysql/mysql.conf.d/mysqld.cnf ]; then sed -i 's/^bind-address.*/bind-address = %s/' /etc/mysql/mysql.conf.d/mysqld.cnf; fi", $privateIp);
        $commands[] = sprintf("if [ -f /etc/redis/redis.conf ]; then sed -i 's/^bind .*/bind 127.0.0.1 %s/' /etc/redis/redis.conf; sed -i 's/^protected-mode .*/protected-mode yes/' /etc/redis/redis.conf; fi", $privateIp);
        $commands[] = sprintf("find /etc/postgresql -path '*/postgresql.conf' -exec sed -ri \"s/^#?listen_addresses\\s*=.*/listen_addresses = '%s'/\" {} \\; 2>/dev/null || true", $privateIp);

        return $commands;
    }

    private function publicPortsFor(Server $server): array
    {
        $roles = $server->clusters()
            ->withPivot(['role', 'is_active'])
            ->get()
            ->pluck('pivot.role')
            ->filter()
            ->unique()
            ->values();

        if ($roles->isEmpty()) {
            return [80, 443];
        }

        $ports = [22];

        foreach ($roles as $role) {
            if ($role === NodeRole::Web->value) {
                $ports[] = 80;
                $ports[] = 443;
            }
        }

        return array_values(array_unique($ports));
    }
}
