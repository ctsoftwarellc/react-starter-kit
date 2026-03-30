<?php

namespace Tests\Unit\Modules\Deployment\Services;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\DeploymentStepStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
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
use Illuminate\Support\Str;
use Tests\TestCase;

class RollbackManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rolls_back_updated_nodes_to_previous_release(): void
    {
        [$environment, $rollbackRelease, $failedDeployment, $rollbackDeployment] = $this->rollbackFixture();

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

        $result = $manager->execute($rollbackDeployment, $failedDeployment);

        $this->assertSame(DeploymentStatus::Succeeded, $result->fresh()->status);
        $this->assertTrue($result->fresh('steps')->steps->every(fn ($step) => $step->status === DeploymentStepStatus::RolledBack));
        $this->assertSame(DeploymentStatus::RolledBack, $failedDeployment->fresh()->status);
        $this->assertSame(ReleaseStatus::RolledBack, $failedDeployment->release->fresh()->status);
        $this->assertSame($rollbackRelease->id, $environment->fresh()->active_release_id);
    }

    public function test_it_keeps_previous_release_active_after_rollback(): void
    {
        [$environment, $rollbackRelease, $failedDeployment, $rollbackDeployment] = $this->rollbackFixture();

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

        $manager->execute($rollbackDeployment, $failedDeployment);

        $this->assertSame($rollbackRelease->id, $environment->fresh()->active_release_id);
        $this->assertSame(ReleaseStatus::Active, $rollbackRelease->fresh()->status);
    }

    private function rollbackFixture(): array
    {
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $rollbackRelease = Release::factory()->create([
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
        $environment->update(['active_release_id' => $rollbackRelease->id]);

        $failedDeployment = Deployment::factory()->create([
            'release_id' => $failedRelease->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Failed,
        ]);
        $rollbackDeployment = Deployment::factory()->create([
            'release_id' => $rollbackRelease->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Pending,
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

        return [$environment, $rollbackRelease, $failedDeployment->fresh('release'), $rollbackDeployment->fresh('release')];
    }
}
