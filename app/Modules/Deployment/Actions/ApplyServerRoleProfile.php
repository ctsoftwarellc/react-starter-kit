<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Models\ServerRoleProfile;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Facades\DB;

class ApplyServerRoleProfile
{
    public function execute(ServerRoleProfile $serverRoleProfile): ServerRoleProfile
    {
        return DB::transaction(function () use ($serverRoleProfile) {
            $environment = $serverRoleProfile->environment()->firstOrFail();

            $servers = Server::query()
                ->select('servers.*')
                ->join('cluster_node', 'cluster_node.server_id', '=', 'servers.id')
                ->where('cluster_node.cluster_id', $environment->cluster_id)
                ->where('cluster_node.role', $serverRoleProfile->role->value)
                ->where('cluster_node.is_active', true)
                ->get();

            foreach ($servers as $server) {
                AgentCommand::create([
                    'server_id' => $server->id,
                    'type' => AgentCommandType::ConfigureService,
                    'payload' => [
                        'environment_id' => $environment->id,
                        'server_role_profile_id' => $serverRoleProfile->id,
                        'role' => $serverRoleProfile->role->value,
                        'config' => $serverRoleProfile->config,
                    ],
                    'status' => AgentCommandStatus::Pending,
                    'expires_at' => now()->addMinutes(10),
                ]);
            }

            return $serverRoleProfile->fresh();
        });
    }
}
