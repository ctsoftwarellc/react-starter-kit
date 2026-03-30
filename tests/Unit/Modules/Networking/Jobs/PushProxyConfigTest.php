<?php

namespace Tests\Unit\Modules\Networking\Jobs;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Infrastructure\Enums\AgentCommandStatus;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\AgentCommand;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Networking\Jobs\PushProxyConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PushProxyConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_update_proxy_config_agent_commands_for_web_nodes(): void
    {
        $cluster = Cluster::factory()->create();
        $webA = Server::factory()->create();
        $webB = Server::factory()->create();
        $worker = Server::factory()->create();
        $environment = Environment::factory()->create(['cluster_id' => $cluster->id]);
        $environment->domains()->create([
            'hostname' => 'app.example.test',
            'is_primary' => true,
            'is_verified' => true,
            'verification_token' => 'token-1',
        ]);

        foreach ([
            [$webA, NodeRole::Web->value, 1],
            [$webB, NodeRole::Web->value, 2],
            [$worker, NodeRole::Worker->value, 3],
        ] as [$server, $role, $sortOrder]) {
            DB::table('cluster_node')->insert([
                'id' => (string) Str::ulid(),
                'cluster_id' => $cluster->id,
                'server_id' => $server->id,
                'role' => $role,
                'is_active' => true,
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        (new PushProxyConfig($cluster))->handle();

        $commands = AgentCommand::query()->orderBy('server_id')->get();

        $this->assertCount(2, $commands);
        $this->assertSame([$webA->id, $webB->id], $commands->pluck('server_id')->all());
        $this->assertTrue($commands->every(fn (AgentCommand $command) => $command->type === AgentCommandType::UpdateProxyConfig));
        $this->assertTrue($commands->every(fn (AgentCommand $command) => $command->status === AgentCommandStatus::Pending));
        $this->assertStringContainsString('app.example.test', $commands->first()->payload['config']);
    }
}
