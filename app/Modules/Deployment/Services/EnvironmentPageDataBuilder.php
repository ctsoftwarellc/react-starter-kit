<?php

namespace App\Modules\Deployment\Services;

use App\Http\Resources\Deployment\DeploymentResource;
use App\Http\Resources\Deployment\HealthCheckResource;
use App\Http\Resources\Deployment\ReleaseResource;
use App\Http\Resources\Deployment\RemoteCommandResource;
use App\Http\Resources\Deployment\RuntimeProfileResource;
use App\Http\Resources\Deployment\ServerRoleProfileResource;
use App\Http\Resources\Infrastructure\ServerResource;
use App\Http\Resources\Pipeline\ArtifactResource;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Pipeline\Enums\ArtifactStatus;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use App\Modules\ServiceManagement\Models\StorageBucket;

class EnvironmentPageDataBuilder
{
    public function execute(Environment $environment): array
    {
        $environment->load([
            'application.project',
            'cluster',
            'variables',
            'secrets',
            'processDefinitions',
            'activeRelease.artifact.pipelineRun.pipeline',
            'runtimeProfile',
            'serverRoleProfiles',
        ]);

        $healthCheck = $environment->healthChecks()->latest('created_at')->first();
        $releases = $environment->releases()
            ->with(['artifact.pipelineRun.pipeline'])
            ->latestVersionFirst()
            ->limit(10)
            ->get();
        $deployments = $environment->deployments()
            ->with(['release.artifact.pipelineRun.pipeline', 'steps.server'])
            ->latestFirst()
            ->limit(10)
            ->get();
        $deployableArtifacts = $environment->application->artifacts()
            ->with(['pipelineRun.pipeline'])
            ->where('status', ArtifactStatus::Ready->value)
            ->latest()
            ->limit(10)
            ->get();
        $runtimeProfiles = $environment->application->runtimeProfiles()->latest()->get();
        $serverRoleProfiles = $environment->serverRoleProfiles()->latest()->get();
        $remoteCommands = $environment->remoteCommands()->with('server')->latest()->limit(10)->get();
        $environmentServers = Server::query()
            ->select('servers.*')
            ->join('cluster_node', 'cluster_node.server_id', '=', 'servers.id')
            ->where('cluster_node.cluster_id', $environment->cluster_id)
            ->where('cluster_node.is_active', true)
            ->orderBy('cluster_node.sort_order')
            ->get();

        return [
            'environment' => $environment,
            'application' => $environment->application,
            'clusters' => Cluster::latest()->get(),
            'databaseInstances' => DatabaseInstance::with('cluster')->latest()->get(),
            'cacheInstances' => CacheInstance::with('cluster')->latest()->get(),
            'storageBuckets' => StorageBucket::latest()->get(),
            'serviceBindings' => $environment->serviceBindings()->with(['databaseInstance.cluster', 'cacheInstance.cluster', 'storageBucket'])->latest()->get(),
            'activeRelease' => $environment->activeRelease ? (new ReleaseResource($environment->activeRelease))->resolve() : null,
            'releases' => ReleaseResource::collection($releases)->resolve(),
            'deployments' => DeploymentResource::collection($deployments)->resolve(),
            'healthCheck' => $healthCheck ? (new HealthCheckResource($healthCheck))->resolve() : null,
            'deployableArtifacts' => ArtifactResource::collection($deployableArtifacts)->resolve(),
            'runtimeProfiles' => RuntimeProfileResource::collection($runtimeProfiles)->resolve(),
            'serverRoleProfiles' => ServerRoleProfileResource::collection($serverRoleProfiles)->resolve(),
            'remoteCommands' => RemoteCommandResource::collection($remoteCommands)->resolve(),
            'environmentServers' => ServerResource::collection($environmentServers)->resolve(),
        ];
    }
}
