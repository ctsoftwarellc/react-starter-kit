<?php

namespace Tests\Feature\Api\Infrastructure;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Enums\RemoteCommandStatus;
use App\Modules\Deployment\Models\RemoteCommand;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createServerWithToken(): array
    {
        $plainToken = 'test-agent-token-string-1234567890';
        $hashedToken = hash('sha256', $plainToken);

        Event::fake();
        $server = Server::factory()->active()->create([
            'agent_token' => $plainToken,
            'agent_token_hash' => $hashedToken,
        ]);

        return [$server, $plainToken];
    }

    public function test_agent_can_heartbeat(): void
    {
        [$server, $token] = $this->createServerWithToken();

        $this->assertNull($server->last_heartbeat_at);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/agent/heartbeat');

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');

        $server->refresh();
        $this->assertNotNull($server->last_heartbeat_at);
    }

    public function test_heartbeat_stores_metrics(): void
    {
        [$server, $token] = $this->createServerWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/agent/heartbeat', [
                'cpu' => 45.5,
                'memory' => 72.3,
                'disk' => 30.1,
                'load' => 1.5,
            ]);

        $response->assertOk();

        $server->refresh();
        $this->assertEquals(45.5, $server->metadata['cpu']);
        $this->assertEquals(72.3, $server->metadata['memory']);
        $this->assertEquals(30.1, $server->metadata['disk']);
        $this->assertEquals(1.5, $server->metadata['load']);
    }

    public function test_agent_can_get_pending_commands(): void
    {
        [$server, $token] = $this->createServerWithToken();

        AgentCommand::create([
            'id' => Str::ulid()->toString(),
            'server_id' => $server->id,
            'type' => AgentCommandType::Deploy,
            'payload' => ['key' => 'value'],
            'status' => AgentCommandStatus::Pending,
            'expires_at' => now()->addHour(),
        ]);

        // Create an expired command that should not appear
        AgentCommand::create([
            'id' => Str::ulid()->toString(),
            'server_id' => $server->id,
            'type' => AgentCommandType::Custom,
            'payload' => [],
            'status' => AgentCommandStatus::Pending,
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/agent/commands/pending');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'deploy');
    }

    public function test_agent_can_report_command_result(): void
    {
        [$server, $token] = $this->createServerWithToken();

        $command = AgentCommand::create([
            'id' => Str::ulid()->toString(),
            'server_id' => $server->id,
            'type' => AgentCommandType::Deploy,
            'payload' => [],
            'status' => AgentCommandStatus::Pending,
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/agent/commands/'.$command->id.'/result', [
                'status' => 'completed',
                'result' => ['output' => 'Success'],
            ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'ok');

        $command->refresh();
        $this->assertEquals(AgentCommandStatus::Completed, $command->status);
        $this->assertEquals(['output' => 'Success'], $command->result);
        $this->assertNotNull($command->completed_at);
    }

    public function test_reporting_remote_command_result_updates_remote_command_history(): void
    {
        [$server, $token] = $this->createServerWithToken();

        $environment = Environment::factory()->create();

        $remoteCommand = RemoteCommand::factory()->create([
            'environment_id' => $environment->id,
            'server_id' => $server->id,
        ]);

        $command = AgentCommand::create([
            'id' => Str::ulid()->toString(),
            'server_id' => $server->id,
            'type' => AgentCommandType::RemoteCommand,
            'payload' => ['remote_command_id' => $remoteCommand->id],
            'status' => AgentCommandStatus::Pending,
            'expires_at' => now()->addHour(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/agent/commands/'.$command->id.'/result', [
                'status' => 'completed',
                'result' => ['output' => 'Done', 'exit_code' => 0],
            ])
            ->assertOk();

        $remoteCommand->refresh();

        $this->assertEquals(RemoteCommandStatus::Succeeded, $remoteCommand->status);
        $this->assertEquals('Done', $remoteCommand->output);
        $this->assertEquals(0, $remoteCommand->exit_code);
        $this->assertNotNull($remoteCommand->finished_at);
    }

    public function test_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/agent/heartbeat');

        $response->assertUnauthorized();
    }

    public function test_no_token_returns_401(): void
    {
        $response = $this->postJson('/api/agent/heartbeat');

        $response->assertUnauthorized();
    }

    public function test_agent_cannot_report_result_for_other_servers_command(): void
    {
        [$server, $token] = $this->createServerWithToken();

        Event::fake();
        $otherServer = Server::factory()->active()->create();

        $command = AgentCommand::create([
            'id' => Str::ulid()->toString(),
            'server_id' => $otherServer->id,
            'type' => AgentCommandType::Deploy,
            'payload' => [],
            'status' => AgentCommandStatus::Pending,
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/agent/commands/'.$command->id.'/result', [
                'status' => 'completed',
                'result' => [],
            ]);

        $response->assertForbidden();
    }
}
