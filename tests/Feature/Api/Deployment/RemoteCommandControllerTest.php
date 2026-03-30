<?php

namespace Tests\Feature\Api\Deployment;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RemoteCommandControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_remote_command(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $server = Server::factory()->active()->create();

        $environment->cluster->servers()->attach($server->id, [
            'id' => (string) Str::ulid(),
            'role' => 'web',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/remote-commands', [
                'type' => 'run_migrations',
            ])
            ->assertCreated()
            ->assertJsonPath('data.environment_id', $environment->id)
            ->assertJsonPath('data.server_id', $server->id)
            ->assertJsonPath('data.type', 'run_migrations');

        $this->assertDatabaseHas('agent_commands', [
            'server_id' => $server->id,
            'type' => AgentCommandType::RemoteCommand->value,
        ]);
    }
}
