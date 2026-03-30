<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Models\RuntimeProfile;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Support\Facades\DB;

class ApplyRuntimeProfile
{
    public function execute(Environment $environment, RuntimeProfile $runtimeProfile): Environment
    {
        return DB::transaction(function () use ($environment, $runtimeProfile) {
            $environment->update([
                'runtime_profile_id' => $runtimeProfile->id,
            ]);

            $this->dispatchConfigurationCommands($environment, $runtimeProfile);

            return $environment->fresh(['runtimeProfile']);
        });
    }

    private function dispatchConfigurationCommands(Environment $environment, RuntimeProfile $runtimeProfile): void
    {
        $servers = Server::query()
            ->select('servers.*')
            ->join('cluster_node', 'cluster_node.server_id', '=', 'servers.id')
            ->where('cluster_node.cluster_id', $environment->cluster_id)
            ->whereIn('cluster_node.role', ['web', 'worker', 'queue'])
            ->where('cluster_node.is_active', true)
            ->get();

        foreach ($servers as $server) {
            AgentCommand::create([
                'server_id' => $server->id,
                'type' => AgentCommandType::ConfigureRuntime,
                'payload' => [
                    'environment_id' => $environment->id,
                    'runtime_profile_id' => $runtimeProfile->id,
                    'stack' => $runtimeProfile->stack->value,
                    'config' => $runtimeProfile->config,
                ],
                'status' => AgentCommandStatus::Pending,
                'expires_at' => now()->addMinutes(10),
            ]);
        }
    }
}
