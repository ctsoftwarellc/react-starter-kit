<?php

namespace Tests\Unit\Modules\Operations\Services;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStrategy;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Infrastructure\Enums\ClusterStatus;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Models\Backup;
use App\Modules\Operations\Services\DashboardDataBuilder;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Enums\TriggerType;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardDataBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_server_cluster_application_and_recent_activity_summaries(): void
    {
        $application = Application::factory()->create(['name' => 'Helm App']);
        $cluster = Cluster::factory()->create(['status' => ClusterStatus::Active]);
        $environment = Environment::factory()->create([
            'application_id' => $application->id,
            'cluster_id' => $cluster->id,
            'name' => 'production',
        ]);

        Server::factory()->active()->count(2)->create();
        Server::factory()->create(['status' => ServerStatus::Failed]);

        Cluster::factory()->create(['status' => ClusterStatus::Degraded]);
        Cluster::factory()->create(['status' => ClusterStatus::Maintenance]);
        Cluster::factory()->create(['status' => ClusterStatus::Pending]);

        $pipeline = Pipeline::factory()->create(['application_id' => $application->id]);

        Deployment::factory()->create([
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Succeeded,
            'strategy' => DeploymentStrategy::Rolling,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(3),
        ]);

        PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'environment_id' => $environment->id,
            'status' => PipelineRunStatus::Succeeded,
            'trigger_type' => TriggerType::Push,
            'started_at' => now()->subMinutes(8),
            'finished_at' => now()->subMinutes(6),
        ]);

        DatabaseInstance::factory()->count(2)->create();
        CacheInstance::factory()->create();

        $backupServer = Server::factory()->create(['status' => ServerStatus::Pending]);
        Backup::factory()->create([
            'server_id' => $backupServer->id,
            'status' => BackupStatus::Completed,
        ]);

        $data = (new DashboardDataBuilder)->build();

        $serverCounts = Server::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        $clusterCounts = Cluster::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $this->assertSame($serverCounts, $data['stats']['server_counts_by_status']);
        $this->assertSame((int) $clusterCounts->sum(), $data['stats']['cluster_health_summary']['total']);
        $this->assertSame((int) ($clusterCounts[ClusterStatus::Active->value] ?? 0), $data['stats']['cluster_health_summary']['healthy']);
        $this->assertSame((int) ($clusterCounts[ClusterStatus::Degraded->value] ?? 0), $data['stats']['cluster_health_summary']['degraded']);
        $this->assertSame((int) ($clusterCounts[ClusterStatus::Maintenance->value] ?? 0), $data['stats']['cluster_health_summary']['maintenance']);
        $this->assertSame(Application::query()->count(), $data['stats']['application_count']);
        $this->assertSame('Helm App', $data['recent_deployments'][0]['application']);
        $this->assertSame('production', $data['recent_pipeline_runs'][0]['environment']);
        $this->assertSame(120, (int) $data['recent_pipeline_runs'][0]['duration_seconds']);
        $this->assertSame(2, $data['service_overview']['database_instances']);
        $this->assertSame(1, $data['service_overview']['cache_instances']);
        $this->assertSame(1, $data['service_overview']['backups']['count']);
        $this->assertSame(BackupStatus::Completed, $data['service_overview']['backups']['latest_status']);
    }
}
