<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\DeployToNode;
use App\Modules\Deployment\Enums\DeploymentStatus;
use App\Modules\Deployment\Enums\ReleaseStatus;
use App\Modules\Deployment\Models\Deployment;
use App\Modules\Deployment\Models\DeploymentStep;
use App\Modules\Deployment\Models\Release;
use App\Modules\Deployment\Services\DeploymentCommandPayloadBuilder;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Pipeline\Models\Artifact;
use App\Support\Services\ObjectStorage\ObjectStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployToNodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_deploy_agent_command_with_expected_payload(): void
    {
        $application = Application::factory()->create();
        $environment = Environment::factory()->create(['application_id' => $application->id]);
        $artifact = Artifact::factory()->create([
            'application_id' => $application->id,
            'storage_path' => 'releases/app.tar.gz',
            'content_hash' => 'abc123',
        ]);
        $release = Release::factory()->create([
            'environment_id' => $environment->id,
            'artifact_id' => $artifact->id,
            'status' => ReleaseStatus::Deploying,
            'config_snapshot' => [
                'env_vars' => ['APP_ENV' => 'production'],
                'secrets' => ['APP_KEY'],
                'processes' => [['type' => 'web', 'command' => 'php-fpm', 'instances' => 2]],
                'runtime' => 'php',
                'runtime_stack' => 'php_fpm',
                'php_version' => '8.4',
                'shared_dirs' => ['storage'],
                'writable_dirs' => ['storage', 'bootstrap/cache'],
                'pre_activate' => ['php artisan migrate --force'],
                'post_activate' => ['php artisan queue:restart'],
            ],
        ]);
        $deployment = Deployment::factory()->create([
            'release_id' => $release->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::Deploying,
        ]);
        $server = Server::factory()->create();
        $step = DeploymentStep::factory()->create([
            'deployment_id' => $deployment->id,
            'server_id' => $server->id,
        ]);

        $storage = $this->createMock(ObjectStorageService::class);
        $storage->method('artifactUrl')->with('releases/app.tar.gz')->willReturn('https://storage.test/artifacts/releases/app.tar.gz');

        $command = (new DeployToNode(new DeploymentCommandPayloadBuilder($storage)))->execute($deployment, $step);

        $this->assertSame(AgentCommandType::Deploy, $command->type);
        $this->assertSame($server->id, $command->server_id);
        $this->assertSame('deploy', $command->payload['command']);
        $this->assertSame($release->id, $command->payload['release_id']);
        $this->assertSame('https://storage.test/artifacts/releases/app.tar.gz', $command->payload['artifact_url']);
        $this->assertSame('sha256:abc123', $command->payload['artifact_hash']);
        $this->assertSame('production', $command->payload['config']['env_vars']['APP_ENV']);
        $this->assertSame(['APP_KEY'], $command->payload['config']['secrets']);
        $this->assertSame(['php artisan migrate --force'], $command->payload['config']['pre_activate']);
    }
}
