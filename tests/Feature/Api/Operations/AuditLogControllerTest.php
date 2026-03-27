<?php

namespace Tests\Feature\Api\Operations;

use App\Actions\CreatePersonalAccessToken;
use App\Models\User;
use App\Modules\AppPlatform\Models\Project;
use App\Modules\Operations\Actions\RecordAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(): array
    {
        $user = User::factory()->create();
        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        return [$user, $result->plainTextToken];
    }

    public function test_list_audit_logs_returns_200(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/audit-logs');

        $response->assertOk();
    }

    public function test_audit_log_created_when_project_is_created(): void
    {
        [$user, $token] = $this->createUserWithToken();

        // Create a project via API - this should trigger the event listener
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/projects', ['name' => 'Audit Test Project']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/audit-logs');

        $response->assertOk();
        $response->assertJsonFragment(['action' => 'project.created']);
    }

    public function test_filter_by_auditable_type_works(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $project = Project::factory()->create();

        (new RecordAuditLog)->execute(
            action: 'project.created',
            auditable: $project,
            user: $user,
        );

        $morphClass = $project->getMorphClass();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/audit-logs?auditable_type='.$morphClass);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['action' => 'project.created']);
    }

    public function test_filter_by_action_works(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $project = Project::factory()->create();

        (new RecordAuditLog)->execute(
            action: 'project.created',
            auditable: $project,
            user: $user,
        );

        (new RecordAuditLog)->execute(
            action: 'project.deleted',
            auditable: $project,
            user: $user,
        );

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/audit-logs?action=project.created');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['action' => 'project.created']);
    }
}
