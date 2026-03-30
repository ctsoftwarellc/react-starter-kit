<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Deployment\Services\DeploymentCommandPayloadBuilder;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\AgentCommand;
use Illuminate\Support\Facades\DB;

class RollbackNodeDeployment
{
    public function __construct(
        private readonly DeploymentCommandPayloadBuilder $payloadBuilder = new DeploymentCommandPayloadBuilder,
    ) {}

    public function execute(Deployment $deployment, DeploymentStep $step): AgentCommand
    {
        return DB::transaction(fn () => AgentCommand::create([
            'server_id' => $step->server_id,
            'type' => AgentCommandType::Rollback,
            'payload' => $this->payloadBuilder->buildRollbackPayload($deployment, $step),
            'status' => AgentCommandStatus::Pending,
            'expires_at' => now()->addMinutes(5),
        ]));
    }
}
