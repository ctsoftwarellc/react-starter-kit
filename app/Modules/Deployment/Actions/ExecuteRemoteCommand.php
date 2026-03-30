<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\RemoteCommandStatus;
use App\Modules\Deployment\Enums\RemoteCommandType;
use App\Modules\Deployment\Events\RemoteCommandExecuted;
use App\Modules\Deployment\Models\RemoteCommand;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ExecuteRemoteCommand
{
    public function execute(Environment $environment, array $attributes): RemoteCommand
    {
        $type = $attributes['type'] instanceof RemoteCommandType
            ? $attributes['type']
            : RemoteCommandType::from($attributes['type']);

        $server = $this->resolveServer($environment, $attributes['server_id'] ?? null);
        $command = $this->resolveCommand($type, $attributes);

        if ($server === null) {
            throw new RuntimeException('No eligible server is available for remote commands.');
        }

        return DB::transaction(function () use ($environment, $server, $type, $command) {
            $remoteCommand = $environment->remoteCommands()->create([
                'server_id' => $server->id,
                'type' => $type,
                'command' => $command,
                'status' => RemoteCommandStatus::Pending,
                'started_at' => now(),
            ]);

            AgentCommand::create([
                'server_id' => $server->id,
                'type' => AgentCommandType::RemoteCommand,
                'payload' => [
                    'command' => 'remote_command',
                    'remote_command_id' => $remoteCommand->id,
                    'template' => $type->value,
                    'shell_command' => $command,
                ],
                'status' => AgentCommandStatus::Pending,
                'expires_at' => now()->addMinutes(10),
            ]);

            event(new RemoteCommandExecuted($remoteCommand));

            return $remoteCommand->fresh(['server']);
        });
    }

    private function resolveServer(Environment $environment, ?string $serverId): ?Server
    {
        $query = Server::query()
            ->select('servers.*')
            ->join('cluster_node', 'cluster_node.server_id', '=', 'servers.id')
            ->where('cluster_node.cluster_id', $environment->cluster_id)
            ->where('cluster_node.is_active', true)
            ->orderBy('cluster_node.sort_order');

        if ($serverId !== null && $serverId !== '') {
            return (clone $query)->where('servers.id', $serverId)->firstOrFail();
        }

        return (clone $query)
            ->whereIn('cluster_node.role', ['web', 'worker', 'queue'])
            ->first();
    }

    private function resolveCommand(RemoteCommandType $type, array $attributes): string
    {
        return match ($type) {
            RemoteCommandType::RunMigrations => 'php artisan migrate --force',
            RemoteCommandType::ClearCache => 'php artisan optimize:clear',
            RemoteCommandType::RestartWorkers => 'php artisan queue:restart',
            RemoteCommandType::ArtisanTinker => sprintf(
                'php artisan tinker --execute=%s',
                escapeshellarg($attributes['script'] ?? 'app()->environment();'),
            ),
            RemoteCommandType::Custom => $this->guardCustomCommand($attributes),
        };
    }

    private function guardCustomCommand(array $attributes): string
    {
        $command = trim((string) ($attributes['command'] ?? ''));

        if (! ($attributes['allow_arbitrary'] ?? false)) {
            throw new RuntimeException('Custom commands require explicit arbitrary-command confirmation.');
        }

        if ($command === '') {
            throw new RuntimeException('A custom command is required.');
        }

        $blockedFragments = [
            'rm -rf /',
            'shutdown',
            'reboot',
            'mkfs',
            'dd if=',
            ':(){:|:&};:',
        ];

        foreach ($blockedFragments as $fragment) {
            if (Str::contains(Str::lower($command), Str::lower($fragment))) {
                throw new RuntimeException('That command is blocked by the MVP safety guard.');
            }
        }

        return $command;
    }
}
