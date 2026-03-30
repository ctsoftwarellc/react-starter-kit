<?php

namespace App\Modules\Operations\Services;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Models\Backup;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use Illuminate\Support\Facades\Schema;

class DashboardDataBuilder
{
    public function build(): array
    {
        return [
            'stats' => [
                'server_counts_by_status' => $this->serverCountsByStatus(),
                'cluster_health_summary' => $this->clusterHealthSummary(),
                'application_count' => Application::query()->count(),
            ],
            'recent_deployments' => $this->recentDeployments(),
            'recent_pipeline_runs' => $this->recentPipelineRuns(),
            'service_overview' => $this->serviceOverview(),
        ];
    }

    private function serverCountsByStatus(): array
    {
        return Server::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    private function clusterHealthSummary(): array
    {
        $counts = Cluster::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => (int) $counts->sum(),
            'healthy' => (int) ($counts['active'] ?? 0),
            'degraded' => (int) ($counts['degraded'] ?? 0),
            'maintenance' => (int) ($counts['maintenance'] ?? 0),
            'other' => (int) ($counts->sum() - ($counts['active'] ?? 0) - ($counts['degraded'] ?? 0) - ($counts['maintenance'] ?? 0)),
        ];
    }

    private function recentDeployments(): array
    {
        return Deployment::query()
            ->with(['environment.application'])
            ->latestFirst()
            ->limit(10)
            ->get()
            ->map(fn (Deployment $deployment) => [
                'id' => $deployment->id,
                'status' => $deployment->status->value,
                'strategy' => $deployment->strategy->value,
                'application' => $deployment->environment?->application?->name,
                'environment' => $deployment->environment?->name,
                'started_at' => $deployment->started_at,
                'finished_at' => $deployment->finished_at,
                'created_at' => $deployment->created_at,
            ])
            ->all();
    }

    private function recentPipelineRuns(): array
    {
        return PipelineRun::query()
            ->with(['environment.application'])
            ->latestFirst()
            ->limit(10)
            ->get()
            ->map(fn (PipelineRun $run) => [
                'id' => $run->id,
                'status' => $run->status->value,
                'trigger_type' => $run->trigger_type->value,
                'application' => $run->environment?->application?->name,
                'environment' => $run->environment?->name,
                'duration_seconds' => $run->started_at && $run->finished_at
                    ? $run->started_at->diffInSeconds($run->finished_at)
                    : null,
                'started_at' => $run->started_at,
                'finished_at' => $run->finished_at,
                'created_at' => $run->created_at,
            ])
            ->all();
    }

    private function serviceOverview(): array
    {
        $overview = [
            'database_instances' => DatabaseInstance::query()->count(),
            'cache_instances' => CacheInstance::query()->count(),
        ];

        if (Schema::hasTable('backups')) {
            $overview['backups'] = [
                'count' => Backup::query()->count(),
                'latest_status' => Backup::query()->latest('created_at')->value('status'),
            ];
        }

        return $overview;
    }
}
