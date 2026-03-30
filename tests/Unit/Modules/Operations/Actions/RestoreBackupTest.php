<?php

namespace Tests\Unit\Modules\Operations\Actions;

use App\Modules\Infrastructure\Enums\NodeRole;
use App\Modules\Infrastructure\Models\Cluster;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Actions\RestoreBackup;
use App\Modules\Operations\Models\Backup;
use App\Modules\Operations\Services\BackupExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RestoreBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_incompatible_restore_target(): void
    {
        $sourceServer = Server::factory()->create(['metadata' => ['database_engine' => 'postgres']]);
        $targetServer = Server::factory()->create(['metadata' => ['database_engine' => 'mysql']]);
        $backup = Backup::factory()->completed()->create([
            'server_id' => $sourceServer->id,
            'type' => 'database',
        ]);

        $executor = Mockery::mock(BackupExecutor::class);
        $executor->shouldReceive('detectDatabaseEngine')->once()->withArgs(fn (Server $server) => $server->is($sourceServer))->andReturn('postgres');
        $executor->shouldReceive('detectDatabaseEngine')->once()->withArgs(fn (Server $server) => $server->is($targetServer))->andReturn('mysql');
        $executor->shouldNotReceive('restore');
        $executor->shouldNotReceive('verifyRestore');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Backup restore target is not compatible with this backup.');

        (new RestoreBackup($executor))->execute($backup, $targetServer);
    }

    public function test_it_allows_file_restore_to_a_server_in_the_same_cluster(): void
    {
        $cluster = Cluster::factory()->create();
        $sourceServer = Server::factory()->create();
        $targetServer = Server::factory()->create();
        foreach ([
            [$sourceServer, NodeRole::Web->value, 1],
            [$targetServer, NodeRole::Worker->value, 2],
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

        $backup = Backup::factory()->completed()->create([
            'server_id' => $sourceServer->id,
            'type' => 'files',
        ]);

        $executor = Mockery::mock(BackupExecutor::class);
        $executor->shouldReceive('restore')->once()->withArgs(fn (Backup $restoredBackup, Server $server) => $restoredBackup->is($backup) && $server->is($targetServer));
        $executor->shouldReceive('verifyRestore')->once()->withArgs(fn (Backup $restoredBackup, Server $server) => $restoredBackup->is($backup) && $server->is($targetServer))->andReturn(true);

        $restored = (new RestoreBackup($executor))->execute($backup, $targetServer);

        $this->assertTrue($restored->is($backup->fresh()));
    }
}
