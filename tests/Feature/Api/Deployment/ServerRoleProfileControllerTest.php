<?php

namespace Tests\Feature\Api\Deployment;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServerRoleProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_applies_a_server_role_profile(): void
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
            ->postJson('/api/v1/environments/'.$environment->id.'/server-role-profiles', [
                'role' => 'web',
                'name' => 'Web Nodes',
                'config' => ['packages' => ['caddy']],
            ])
            ->assertCreated()
            ->assertJsonPath('data.environment_id', $environment->id)
            ->assertJsonPath('data.role', 'web');

        $this->assertDatabaseHas('server_role_profiles', [
            'environment_id' => $environment->id,
            'role' => 'web',
        ]);
        $this->assertDatabaseHas('agent_commands', [
            'server_id' => $server->id,
            'type' => AgentCommandType::ConfigureService->value,
        ]);
    }
}
