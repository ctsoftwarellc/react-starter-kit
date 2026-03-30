<?php

namespace Tests\Integration\Deployment;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\RollbackDeployment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Jobs\ExecuteRollback;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Deployment\Models\Release;
use App\Modules\Deployment\Services\RollbackManager;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class RollbackWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_deployment_triggers_rollback_to_previous_release(): void
    {
        Bus::fake();

        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $previousRelease = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Active,
            'version' => 1,
        ]);
        $failedRelease = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Failed,
            'version' => 2,
        ]);
        $environment->update(['active_release_id' => $previousRelease->id]);

        $failedDeployment = Deployment::factory()->create([
            'release_id' => $failedRelease->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Failed,
        ]);

        foreach ([1, 2] as $sortOrder) {
            $server = Server::factory()->active()->create();
            $environment->cluster->servers()->attach($server->id, [
                'id' => (string) Str::ulid(),
                'role' => 'web',
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);

            DeploymentStep::factory()->create([
                'deployment_id' => $failedDeployment->id,
                'server_id' => $server->id,
                'status' => DeploymentStepStatus::Active,
            ]);
        }

        $rollbackDeployment = (new RollbackDeployment)->execute($failedDeployment);

        Bus::assertDispatched(ExecuteRollback::class, 1);

        $manager = $this->getMockBuilder(RollbackManager::class)
            ->onlyMethods(['rollbackServer'])
            ->getMock();

        $manager->method('rollbackServer')
            ->willReturnCallback(function (DeploymentStep $step, Release $release): AgentCommand {
                return AgentCommand::create([
                    'server_id' => $step->server_id,
                    'type' => AgentCommandType::Rollback,
                    'payload' => ['release_id' => $release->id],
                    'status' => AgentCommandStatus::Completed,
                    'result' => ['message' => 'rolled back'],
                    'expires_at' => now()->addMinute(),
                    'completed_at' => now(),
                ]);
            });

        $result = $manager->execute($rollbackDeployment->fresh(), $failedDeployment->fresh());

        $this->assertSame(DeploymentStatus::Succeeded, $result->fresh()->status);
        $this->assertSame(DeploymentStatus::RolledBack, $failedDeployment->fresh()->status);
        $this->assertSame(ReleaseStatus::RolledBack, $failedRelease->fresh()->status);
        $this->assertSame($previousRelease->id, $environment->fresh()->active_release_id);
    }
}
