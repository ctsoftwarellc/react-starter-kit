<?php

namespace Tests\Unit\Modules\Operations\Actions;

use App\Modules\Infrastructure\Models\Server;
use App\Modules\Operations\Actions\CreateBackup;
use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Jobs\ExecuteBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CreateBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pending_backup_and_queues_execution(): void
    {
        Bus::fake();

        $server = Server::factory()->create();

        $backup = (new CreateBackup)->execute($server, [
            'type' => 'database',
            'retention_days' => 14,
        ]);

        $this->assertSame(BackupStatus::Pending, $backup->status);
        $this->assertSame(14, $backup->retention_days);

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'server_id' => $server->id,
            'type' => 'database',
            'retention_days' => 14,
        ]);

        Bus::assertDispatched(ExecuteBackup::class, fn (ExecuteBackup $job) => $job->backup->is($backup));
    }
}
