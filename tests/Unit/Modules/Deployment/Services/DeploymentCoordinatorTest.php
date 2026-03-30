<?php

namespace Tests\Unit\Modules\Deployment\Services;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\Release;
use App\Modules\Deployment\Services\DeploymentCoordinator;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class DeploymentCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_only_active_non_cordoned_web_nodes_in_sort_order(): void
    {
        $environment = Environment::factory()->create();
        $cluster = $environment->cluster;

        $second = Server::factory()->active()->create(['name' => 'web-2']);
        $first = Server::factory()->active()->create(['name' => 'web-1']);
        $cordoned = Server::factory()->create(['status' => ServerStatus::Cordoned]);
        $worker = Server::factory()->active()->create();

        $cluster->servers()->attach($second->id, ['id' => (string) Str::ulid(), 'role' => 'web', 'is_active' => true, 'sort_order' => 2]);
        $cluster->servers()->attach($first->id, ['id' => (string) Str::ulid(), 'role' => 'web', 'is_active' => true, 'sort_order' => 1]);
        $cluster->servers()->attach($cordoned->id, ['id' => (string) Str::ulid(), 'role' => 'web', 'is_active' => true, 'sort_order' => 0]);
        $cluster->servers()->attach($worker->id, ['id' => (string) Str::ulid(), 'role' => 'worker', 'is_active' => true, 'sort_order' => 3]);

        $servers = (new DeploymentCoordinator)->resolveTargetServers($environment);

        $this->assertSame([$first->id, $second->id], $servers->pluck('id')->all());
    }

    public function test_it_stops_and_marks_failed_when_a_node_deploy_fails(): void
    {
        $deployment = $this->deploymentWithWebServers(1);

        $coordinator = $this->getMockBuilder(DeploymentCoordinator::class)
            ->onlyMethods(['waitForAgentCommandResult'])
            ->getMock();

        $coordinator->method('waitForAgentCommandResult')
            ->willReturnCallback(function (AgentCommand $command): AgentCommand {
                $command->forceFill([
                    'status' => AgentCommandStatus::Failed,
                    'result' => ['message' => 'agent failed'],
                    'completed_at' => now(),
                ])->save();

                return $command->fresh();
            });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('agent failed');

        try {
            $coordinator->execute($deployment);
        } finally {
            $this->assertSame(DeploymentStatus::Failed, $deployment->fresh()->status);
            $this->assertSame(ReleaseStatus::Failed, $deployment->release->fresh()->status);
        }
    }

    public function test_it_marks_deployment_successful_when_all_steps_become_active(): void
    {
        $deployment = $this->deploymentWithWebServers(2);

        $coordinator = $this->getMockBuilder(DeploymentCoordinator::class)
            ->onlyMethods(['waitForAgentCommandResult'])
            ->getMock();

        $coordinator->method('waitForAgentCommandResult')
            ->willReturnCallback(function (AgentCommand $command): AgentCommand {
                $command->forceFill([
                    'status' => AgentCommandStatus::Completed,
                    'result' => ['message' => 'done'],
                    'completed_at' => now(),
                ])->save();

                return $command->fresh();
            });

        $result = $coordinator->execute($deployment);

        $this->assertSame(DeploymentStatus::Succeeded, $result->fresh()->status);
        $this->assertSame(2, $result->fresh()->completed_nodes);
        $this->assertCount(2, $result->fresh('steps')->steps);
        $this->assertTrue($result->fresh('steps')->steps->every(fn ($step) => $step->status->value === 'active'));
        $this->assertSame($result->release_id, $result->environment->fresh()->active_release_id);
        $this->assertSame(ReleaseStatus::Active, $result->release->fresh()->status);
    }

    private function deploymentWithWebServers(int $count): Deployment
    {
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $release = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => Artifact::factory()->create(['application_id' => $application->id])->id,
            'status' => ReleaseStatus::Deploying,
        ]);
        $deployment = Deployment::factory()->create([
            'release_id' => $release->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Pending,
        ]);

        for ($i = 1; $i <= $count; $i++) {
            $server = Server::factory()->active()->create();
            $environment->cluster->servers()->attach($server->id, [
                'id' => (string) Str::ulid(),
                'role' => 'web',
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        return $deployment->fresh(['release', 'environment.cluster']);
    }
}
