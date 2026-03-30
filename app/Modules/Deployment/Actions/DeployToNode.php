<?php

namespace App\Modules\Deployment\Actions;

use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Deployment\Services\DeploymentCommandPayloadBuilder;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\AgentCommand;
use Illuminate\Support\Facades\DB;

class DeployToNode
{
    public function __construct(
        private readonly DeploymentCommandPayloadBuilder $payloadBuilder = new DeploymentCommandPayloadBuilder,
    ) {}

    public function execute(Deployment $deployment, DeploymentStep $step): AgentCommand
    {
        $deployment->loadMissing([
            'release.artifact',
            'environment.application',
            'environment.variables',
            'environment.secrets',
            'environment.processDefinitions',
            'environment.healthChecks',
        ]);
        $step->loadMissing('server');

        return DB::transaction(fn () => AgentCommand::create([
            'server_id' => $step->server_id,
            'type' => AgentCommandType::Deploy,
            'payload' => $this->payloadBuilder->buildDeployPayload($deployment, $step),
            'status' => AgentCommandStatus::Pending,
            'expires_at' => now()->addMinutes(5),
        ]));
    }
}
