<?php

namespace Tests\Feature\Api\Operations;

use App\Models\User;
use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Jobs\ExecuteBackup;
use App\Modules\Operations\Models\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_server_backups(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $server = Server::factory()->create();
        Backup::factory()->count(2)->create(['server_id' => $server->id]);
        Backup::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/servers/'.$server->id.'/backups')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_triggers_backup_creation(): void
    {
        Bus::fake();

        /** @var User $user */
        $user = User::factory()->create();
        $server = Server::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/servers/'.$server->id.'/backups', [
                'type' => 'files',
                'retention_days' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('data.type', 'files')
            ->assertJsonPath('data.status', BackupStatus::Pending->value);

        Bus::assertDispatched(ExecuteBackup::class);
    }

    public function test_it_requests_backup_restore(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Storage::fake(config('helm.storage_disk'));

        $server = Server::factory()->create();
        $backup = Backup::factory()->completed()->create([
            'server_id' => $server->id,
            'type' => 'files',
            'storage_path' => 'backups/'.$server->id.'/fixture-files.tar.gz',
        ]);

        Storage::disk(config('helm.storage_disk'))->put($backup->storage_path, 'backup-payload');

        $ssh = \Mockery::mock('overload:App\Modules\Infrastructure\Services\SshService');
        $ssh->shouldReceive('connect')->once()->with($server->public_ip, $server->ssh_port, $server->ssh_user);
        $ssh->shouldReceive('upload')->once()->withArgs(fn (string $remotePath, string $contents) => str_contains($remotePath, $backup->id) && $contents === 'backup-payload');
        $ssh->shouldReceive('execute')->times(3)->andReturn('__HELM_RESTORE_OK__', '__HELM_RESTORE_VERIFIED__', '');
        $ssh->shouldReceive('disconnect')->once();

        $this->actingAs($user)
            ->postJson('/api/v1/backups/'.$backup->id.'/restore', [
                'server_id' => $server->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $backup->id)
            ->assertJsonPath('data.server_id', $server->id);

        $this->assertTrue(Storage::disk(config('helm.storage_disk'))->exists('restores/'.$server->id.'/'.$backup->id.'.json'));
    }
}
