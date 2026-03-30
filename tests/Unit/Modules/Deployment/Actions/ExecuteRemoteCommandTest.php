<?php

namespace Tests\Unit\Modules\Deployment\Actions;

use App\Modules\AppPlatform\Models\Environment;
use App\Modules\Deployment\Actions\ExecuteRemoteCommand;
use App\Modules\Deployment\Enums\RemoteCommandType;
use App\Modules\Infrastructure\Enums\AgentCommandType;
use App\Modules\Infrastructure\Enums\ServerStatus;
use App\Modules\Infrastructure\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class ExecuteRemoteCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_remote_command_and_agent_command(): void
    {
        $environment = Environment::factory()->create();
        $server = Server::factory()->create(['status' => ServerStatus::Active]);

        DB::table('cluster_node')->insert([
            'id' => (string) str()->ulid(),
            'cluster_id' => $environment->cluster_id,
            'server_id' => $server->id,
            'role' => 'web',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $remoteCommand = (new ExecuteRemoteCommand)->execute($environment, [
            'type' => RemoteCommandType::RunMigrations,
        ]);

        $this->assertDatabaseHas('remote_commands', [
            'id' => $remoteCommand->id,
            'server_id' => $server->id,
            'type' => RemoteCommandType::RunMigrations->value,
            'command' => 'php artisan migrate --force',
        ]);

        $this->assertDatabaseHas('agent_commands', [
            'server_id' => $server->id,
            'type' => AgentCommandType::RemoteCommand->value,
        ]);
    }

    public function test_it_blocks_dangerous_custom_commands(): void
    {
        $this->expectException(RuntimeException::class);

        $environment = Environment::factory()->create();
        $server = Server::factory()->create(['status' => ServerStatus::Active]);

        DB::table('cluster_node')->insert([
            'id' => (string) str()->ulid(),
            'cluster_id' => $environment->cluster_id,
            'server_id' => $server->id,
            'role' => 'web',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new ExecuteRemoteCommand)->execute($environment, [
            'type' => RemoteCommandType::Custom,
            'command' => 'rm -rf /var/www/current',
            'allow_arbitrary' => true,
        ]);
    }
}
