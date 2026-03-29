<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\DeleteGitConnection;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\AppPlatform\Models\GitConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DeleteGitConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_unused_git_connection(): void
    {
        $connection = GitConnection::factory()->create();

        (new DeleteGitConnection)->execute($connection);

        $this->assertDatabaseMissing('git_connections', ['id' => $connection->id]);
    }

    public function test_it_rejects_deletion_when_applications_are_attached(): void
    {
        $connection = GitConnection::factory()->create();
        Application::factory()->create(['git_connection_id' => $connection->id]);

        $this->expectException(InvalidArgumentException::class);

        (new DeleteGitConnection)->execute($connection);
    }
}
