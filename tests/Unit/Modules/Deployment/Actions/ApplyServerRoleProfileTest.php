<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\ApplyServerRoleProfile;
use App\Modules\Deployment\Models\ServerRoleProfile;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplyServerRoleProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_service_configuration_commands_for_matching_role_nodes(): void
    {
        $environment = Environment::factory()->create();
        $serverRoleProfile = ServerRoleProfile::factory()->create([
            'environment_id' => $environment->id,
            'role' => NodeRole::Web,
        ]);
        $server = Server::factory()->active()->create();

        $environment->cluster->servers()->attach($server->id, [
            'id' => (string) Str::ulid(),
            'role' => 'web',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        (new ApplyServerRoleProfile)->execute($serverRoleProfile);

        $this->assertDatabaseHas('agent_commands', [
            'server_id' => $server->id,
            'type' => AgentCommandType::ConfigureService->value,
        ]);
    }
}
