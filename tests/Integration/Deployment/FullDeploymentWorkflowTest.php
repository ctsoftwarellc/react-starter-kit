<?php

namespace Tests\Integration\Deployment;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\InitiateDeployment;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Jobs\ExecuteDeployment;
use App\Modules\Deployment\Services\DeploymentCoordinator;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Pipeline\Models\Artifact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\TestCase;

class FullDeploymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_release_then_deploy_then_verify_then_activate(): void
    {
        Bus::fake([ExecuteDeployment::class]);

        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $artifact = Artifact::factory()->create(['application_id' => $application->id]);

        foreach ([1, 2] as $sortOrder) {
            $server = Server::factory()->active()->create();
            $environment->cluster->servers()->attach($server->id, [
                'id' => (string) Str::ulid(),
                'role' => 'web',
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }

        $deployment = (new InitiateDeployment)->execute($environment, $artifact);

        Bus::assertDispatched(ExecuteDeployment::class, 1);
        $this->assertSame(ReleaseStatus::Deploying, $deployment->release->fresh()->status);

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

        $result = $coordinator->execute($deployment->fresh());

        $this->assertSame(DeploymentStatus::Succeeded, $result->fresh()->status);
        $this->assertSame(ReleaseStatus::Active, $result->release->fresh()->status);
        $this->assertSame($result->release_id, $environment->fresh()->active_release_id);
        $this->assertTrue($result->fresh('steps')->steps->every(fn ($step) => $step->status->value === 'active'));
    }
}
