<?php

namespace Tests\Feature\Api\Infrastructure;

use App\Actions\CreatePersonalAccessToken;
use App\Models\User;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Jobs\BootstrapServer;
use App\Modules\Infrastructure\Models\Provider;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ServerControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(): array
    {
        $user = User::factory()->create();
        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        return [$user, $result->plainTextToken];
    }

    public function test_can_list_servers(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        Server::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/servers');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_can_create_server(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/servers', [
                'name' => 'web-1',
                'hostname' => 'web-1.example.com',
                'public_ip' => '1.2.3.4',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'web-1');
        $response->assertJsonPath('data.status', 'pending');
    }

    public function test_can_show_server(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $server = Server::factory()->create(['name' => 'show-test']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/servers/'.$server->id);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'show-test');
    }

    public function test_can_update_server(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $server = Server::factory()->create(['name' => 'old-name']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/servers/'.$server->id, [
                'name' => 'new-name',
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'new-name');
    }

    public function test_can_delete_server(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $server = Server::factory()->create(['status' => ServerStatus::Pending]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/servers/'.$server->id);

        $response->assertNoContent();
        $this->assertSoftDeleted('servers', ['id' => $server->id]);
    }

    public function test_can_bootstrap_server(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Bus::fake();
        Event::fake();

        $server = Server::factory()->pending()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/servers/'.$server->id.'/bootstrap');

        $response->assertStatus(202);
        $response->assertJsonPath('message', 'Bootstrap job dispatched.');

        Bus::assertDispatched(BootstrapServer::class);
    }

    public function test_can_drain_server(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $server = Server::factory()->active()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/servers/'.$server->id.'/drain');

        $response->assertOk();
        $response->assertJsonPath('data.status', 'draining');
    }

    public function test_can_cordon_server(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $server = Server::factory()->active()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/servers/'.$server->id.'/cordon');

        $response->assertOk();
        $response->assertJsonPath('data.status', 'cordoned');
    }

    public function test_can_activate_server(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $server = Server::factory()->create(['status' => ServerStatus::Draining]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/servers/'.$server->id.'/activate');

        $response->assertOk();
        $response->assertJsonPath('data.status', 'active');
    }

    public function test_agent_token_never_exposed(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $server = Server::factory()->create([
            'agent_token' => 'secret-agent-token',
            'agent_token_hash' => hash('sha256', 'secret-agent-token'),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/servers/'.$server->id);

        $response->assertOk();
        $response->assertJsonMissingPath('data.agent_token');
        $response->assertJsonMissingPath('data.agent_token_hash');
    }

    public function test_create_validation_fails_missing_required_fields(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/servers', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'hostname', 'public_ip']);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/servers');

        $response->assertUnauthorized();
    }

    public function test_can_create_server_with_provider(): void
    {
        [$user, $token] = $this->createUserWithToken();

        Event::fake();
        $provider = Provider::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/servers', [
                'name' => 'web-2',
                'hostname' => 'web-2.example.com',
                'public_ip' => '5.6.7.8',
                'provider_id' => $provider->id,
                'ssh_port' => 2222,
                'ssh_user' => 'deploy',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.provider_id', $provider->id);
        $response->assertJsonPath('data.ssh_port', 2222);
    }
}
