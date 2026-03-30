<?php

namespace Tests\Unit\Modules\Operations\Models;

use App\Modules\Operations\Enums\BackupStatus;
use App\Modules\Operations\Models\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BackupStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_each_supported_transition(): void
    {
        $transitions = [
            [BackupStatus::Pending, BackupStatus::Running],
            [BackupStatus::Pending, BackupStatus::Failed],
            [BackupStatus::Running, BackupStatus::Completed],
            [BackupStatus::Running, BackupStatus::Failed],
            [BackupStatus::Completed, BackupStatus::Expired],
        ];

        foreach ($transitions as [$from, $to]) {
            $backup = Backup::factory()->create(['status' => $from]);
            $backup->transitionTo($to);
            $this->assertSame($to, $backup->fresh()->status);
        }
    }

    public function test_it_rejects_invalid_terminal_transitions(): void
    {
        $backup = Backup::factory()->create(['status' => BackupStatus::Failed]);

        $this->expectException(InvalidArgumentException::class);

        $backup->transitionTo(BackupStatus::Completed);
    }

    public function test_can_transition_to_matches_backup_lifecycle(): void
    {
        $completed = Backup::factory()->create(['status' => BackupStatus::Completed]);
        $expired = Backup::factory()->create(['status' => BackupStatus::Expired]);

        $this->assertTrue($completed->canTransitionTo(BackupStatus::Expired));
        $this->assertFalse($completed->canTransitionTo(BackupStatus::Running));
        $this->assertFalse($expired->canTransitionTo(BackupStatus::Completed));
    }
}
