<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\ApplyRuntimeProfile;
use App\Modules\Deployment\Models\RuntimeProfile;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplyRuntimeProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_assigns_the_runtime_profile_and_queues_configuration_commands(): void
    {
        $environment = Environment::factory()->create();
        $runtimeProfile = RuntimeProfile::factory()->create([
            'application_id' => $environment->application_id,
        ]);
        $server = Server::factory()->active()->create();

        $environment->cluster->servers()->attach($server->id, [
            'id' => (string) Str::ulid(),
            'role' => 'web',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        (new ApplyRuntimeProfile)->execute($environment, $runtimeProfile);

        $this->assertSame($runtimeProfile->id, $environment->fresh()->runtime_profile_id);
        $this->assertDatabaseHas('agent_commands', [
            'server_id' => $server->id,
            'type' => AgentCommandType::ConfigureRuntime->value,
        ]);
    }
}
