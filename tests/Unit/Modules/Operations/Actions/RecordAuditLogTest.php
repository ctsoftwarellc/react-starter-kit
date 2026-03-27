<?php

namespace Tests\Unit\Modules\Operations\Actions;

use App\Models\User;
use App\Modules\AppPlatform\Models\Project;
use App\Modules\Operations\Actions\RecordAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_audit_log_entry_with_correct_fields(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $log = (new RecordAuditLog)->execute(
            action: 'project.created',
            auditable: $project,
            oldValues: null,
            newValues: ['name' => $project->name],
            user: $user,
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'action' => 'project.created',
            'auditable_type' => $project->getMorphClass(),
            'auditable_id' => $project->id,
        ]);

        $this->assertEquals(['name' => $project->name], $log->new_values);
        $this->assertNull($log->old_values);
    }

    public function test_it_handles_null_user_for_system_events(): void
    {
        $project = Project::factory()->create();

        // Ensure no user is authenticated
        $this->assertNull(auth()->user());

        $log = (new RecordAuditLog)->execute(
            action: 'project.created',
            auditable: $project,
            user: null,
        );

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'user_id' => null,
            'action' => 'project.created',
        ]);
    }
}
