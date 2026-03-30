<?php

namespace App\Modules\Operations\Listeners;

use App\Modules\AppPlatform\Events\ApplicationCreated;
use App\Modules\AppPlatform\Events\EnvironmentConfigChanged;
use App\Modules\AppPlatform\Events\GitConnectionEstablished;
use App\Modules\AppPlatform\Events\ProjectCreated;
use App\Modules\AppPlatform\Events\ProjectDeleted;
use App\Modules\AppPlatform\Events\ProjectUpdated;
use App\Modules\AppPlatform\Events\SecretUpdated;
use App\Modules\Deployment\Events\DeploymentCompleted;
use App\Modules\Deployment\Events\DeploymentFailed;
use App\Modules\Deployment\Events\DeploymentStarted;
use App\Modules\Deployment\Events\RemoteCommandExecuted;
use App\Modules\Deployment\Events\RollbackCompleted;
use App\Modules\Infrastructure\Events\ClusterTopologyChanged;
use App\Modules\Infrastructure\Events\ProviderCreated;
use App\Modules\Infrastructure\Events\ProviderDeleted;
use App\Modules\Infrastructure\Events\ProviderUpdated;
use App\Modules\Infrastructure\Events\ServerBootstrapped;
use App\Modules\Infrastructure\Events\ServerHealthChanged;
use App\Modules\Infrastructure\Events\ServerRegistered;
use App\Modules\Operations\Actions\RecordAuditLog as RecordAuditLogAction;
use App\Modules\Pipeline\Events\ArtifactCreated;
use App\Modules\Pipeline\Events\PipelineJobCompleted;
use App\Modules\Pipeline\Events\PipelineRunCompleted;
use App\Modules\Pipeline\Events\PipelineRunStarted;

class RecordAuditLog
{
    private const EVENT_ACTION_MAP = [
        ProjectCreated::class => 'project.created',
        ProjectUpdated::class => 'project.updated',
        ProjectDeleted::class => 'project.deleted',
        GitConnectionEstablished::class => 'git_connection.established',
        ApplicationCreated::class => 'application.created',
        EnvironmentConfigChanged::class => 'environment.config_changed',
        SecretUpdated::class => 'secret.updated',
        ProviderCreated::class => 'provider.created',
        ProviderUpdated::class => 'provider.updated',
        ProviderDeleted::class => 'provider.deleted',
        ServerRegistered::class => 'server.registered',
        ServerBootstrapped::class => 'server.bootstrapped',
        ServerHealthChanged::class => 'server.health_changed',
        ClusterTopologyChanged::class => 'cluster.topology_changed',
        PipelineRunStarted::class => 'pipeline_run.started',
        PipelineRunCompleted::class => 'pipeline_run.completed',
        PipelineJobCompleted::class => 'pipeline_job.completed',
        ArtifactCreated::class => 'artifact.created',
        DeploymentStarted::class => 'deployment.started',
        DeploymentCompleted::class => 'deployment.completed',
        DeploymentFailed::class => 'deployment.failed',
        RollbackCompleted::class => 'deployment.rollback_completed',
        RemoteCommandExecuted::class => 'remote_command.executed',
    ];

    public function handle(object $event): void
    {
        $action = self::EVENT_ACTION_MAP[$event::class] ?? null;

        if ($action === null) {
            return;
        }

        $auditable = $this->getAuditable($event);
        [$oldValues, $newValues] = $this->getValues($event);

        (new RecordAuditLogAction)->execute(
            action: $action,
            auditable: $auditable,
            oldValues: $oldValues,
            newValues: $newValues,
        );
    }

    private function getAuditable(object $event): mixed
    {
        return $event->project
            ?? $event->gitConnection
            ?? $event->application
            ?? $event->environment
            ?? $event->secret
            ?? $event->provider
            ?? $event->server
            ?? $event->cluster
            ?? $event->pipelineRun
            ?? $event->pipelineJob
            ?? $event->artifact
            ?? $event->deployment
            ?? $event->remoteCommand
            ?? null;
    }

    private function getValues(object $event): array
    {
        if ($event instanceof ProjectUpdated) {
            return [$event->oldValues, $event->newValues];
        }

        if ($event instanceof ProjectCreated) {
            return [null, [
                'name' => $event->project->name,
                'slug' => $event->project->slug,
                'description' => $event->project->description,
            ]];
        }

        if ($event instanceof ProjectDeleted) {
            return [[
                'name' => $event->project->name,
                'slug' => $event->project->slug,
            ], null];
        }

        if ($event instanceof ProviderCreated) {
            return [null, [
                'name' => $event->provider->name,
                'type' => $event->provider->type->value,
            ]];
        }

        if ($event instanceof GitConnectionEstablished) {
            return [null, [
                'provider' => $event->gitConnection->provider->value,
                'account_name' => $event->gitConnection->account_name,
            ]];
        }

        if ($event instanceof ApplicationCreated) {
            return [null, [
                'name' => $event->application->name,
                'slug' => $event->application->slug,
                'runtime' => $event->application->runtime->value,
                'repository_url' => $event->application->repository_url,
            ]];
        }

        if ($event instanceof EnvironmentConfigChanged) {
            return [null, [
                'change_type' => $event->changeType,
                'changes' => $event->changes,
            ]];
        }

        if ($event instanceof SecretUpdated) {
            return [null, [
                'change_type' => $event->changeType,
                'metadata' => $event->metadata,
            ]];
        }

        if ($event instanceof ProviderUpdated) {
            return [null, [
                'name' => $event->provider->name,
            ]];
        }

        if ($event instanceof ProviderDeleted) {
            return [[
                'name' => $event->provider->name,
                'type' => $event->provider->type->value,
            ], null];
        }

        if ($event instanceof ServerRegistered) {
            return [null, [
                'name' => $event->server->name,
                'hostname' => $event->server->hostname,
                'public_ip' => $event->server->public_ip,
            ]];
        }

        if ($event instanceof ServerBootstrapped) {
            return [null, [
                'name' => $event->server->name,
                'status' => $event->server->status->value,
            ]];
        }

        if ($event instanceof ServerHealthChanged) {
            return [
                ['status' => $event->previousStatus],
                ['status' => $event->newStatus],
            ];
        }

        if ($event instanceof ClusterTopologyChanged) {
            return [null, [
                'change_type' => $event->changeType,
            ]];
        }

        if ($event instanceof PipelineRunStarted || $event instanceof PipelineRunCompleted) {
            return [null, [
                'status' => $event->pipelineRun->status->value,
                'trigger_type' => $event->pipelineRun->trigger_type->value,
                'trigger_ref' => $event->pipelineRun->trigger_ref,
            ]];
        }

        if ($event instanceof PipelineJobCompleted) {
            return [null, [
                'stage' => $event->pipelineJob->stage,
                'name' => $event->pipelineJob->name,
                'status' => $event->pipelineJob->status->value,
                'exit_code' => $event->pipelineJob->exit_code,
            ]];
        }

        if ($event instanceof ArtifactCreated) {
            return [null, [
                'status' => $event->artifact->status->value,
                'content_hash' => $event->artifact->content_hash,
                'size_bytes' => $event->artifact->size_bytes,
            ]];
        }

        if ($event instanceof DeploymentStarted || $event instanceof DeploymentCompleted || $event instanceof DeploymentFailed || $event instanceof RollbackCompleted) {
            return [null, [
                'status' => $event->deployment->status->value,
                'release_id' => $event->deployment->release_id,
                'environment_id' => $event->deployment->environment_id,
                'strategy' => $event->deployment->strategy->value,
            ]];
        }

        if ($event instanceof RemoteCommandExecuted) {
            return [null, [
                'environment_id' => $event->remoteCommand->environment_id,
                'server_id' => $event->remoteCommand->server_id,
                'type' => $event->remoteCommand->type->value,
                'command' => $event->remoteCommand->command,
                'status' => $event->remoteCommand->status->value,
            ]];
        }

        return [null, null];
    }
}
