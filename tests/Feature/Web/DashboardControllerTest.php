<?php

namespace Tests\Feature\Web;

use App\Models\User;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Infrastructure\Enums\ClusterStatus;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Models\Backup;
use App\Modules\Pipeline\Enums\PipelineRunStatus;
use App\Modules\Pipeline\Models\Pipeline;
use App\Modules\Pipeline\Models\PipelineRun;
use App\Modules\ServiceManagement\Models\CacheInstance;
use App\Modules\ServiceManagement\Models\DatabaseInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_renders_real_summary_data(): void
    {
        $this->withoutVite();

        /** @var User $user */
        $user = User::factory()->create();
        $application = Application::factory()->create(['name' => 'Dashboard App']);
        $cluster = Cluster::factory()->create(['status' => ClusterStatus::Active]);
        $environment = Environment::factory()->create(['application_id' => $application->id, 'cluster_id' => $cluster->id, 'name' => 'production']);
        $pipeline = Pipeline::factory()->create(['application_id' => $application->id]);

        Server::factory()->create(['status' => ServerStatus::Active]);
        Server::factory()->create(['status' => ServerStatus::Failed]);
        Cluster::factory()->create(['status' => ClusterStatus::Active]);
        Cluster::factory()->create(['status' => ClusterStatus::Degraded]);
        Deployment::factory()->create(['environment_id' => $environment->id, 'status' => DeploymentStatus::Succeeded]);
        PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'environment_id' => $environment->id,
            'status' => PipelineRunStatus::Succeeded,
            'started_at' => now()->subMinutes(3),
            'finished_at' => now()->subMinute(),
        ]);
        DatabaseInstance::factory()->create();
        CacheInstance::factory()->create();
        Backup::factory()->create([
            'server_id' => Server::factory()->create(['status' => ServerStatus::Pending])->id,
            'status' => BackupStatus::Completed,
        ]);

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

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('stats.application_count', Application::query()->count())
                ->where('stats.server_counts_by_status.active', $serverCounts[ServerStatus::Active->value] ?? 0)
                ->where('stats.server_counts_by_status.failed', $serverCounts[ServerStatus::Failed->value] ?? 0)
                ->where('stats.cluster_health_summary.healthy', (int) ($clusterCounts[ClusterStatus::Active->value] ?? 0))
                ->where('stats.cluster_health_summary.degraded', (int) ($clusterCounts[ClusterStatus::Degraded->value] ?? 0))
                ->where('recent_deployments.0.application', 'Dashboard App')
                ->where('recent_pipeline_runs.0.environment', 'production')
                ->where('service_overview.database_instances', 1)
                ->where('service_overview.cache_instances', 1)
                ->where('service_overview.backups.latest_status', BackupStatus::Completed->value));
    }
}
