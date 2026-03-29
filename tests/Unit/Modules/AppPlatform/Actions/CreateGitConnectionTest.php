<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\CreateGitConnection;
use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Events\GitConnectionEstablished;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateGitConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_git_connection_with_encrypted_tokens(): void
    {
        Event::fake();

        $expiresAt = CarbonImmutable::parse('2026-05-01 12:00:00');

        $connection = (new CreateGitConnection)->execute(
            GitProvider::Github,
            'access-token',
            'refresh-token',
            $expiresAt,
            'caleb',
        );

        $this->assertDatabaseHas('git_connections', [
            'id' => $connection->id,
            'account_name' => 'caleb',
        ]);
        $this->assertNotSame('access-token', $connection->getRawOriginal('access_token'));
        $this->assertNotSame('refresh-token', $connection->getRawOriginal('refresh_token'));
        $this->assertSame('access-token', $connection->access_token);
        $this->assertSame('refresh-token', $connection->refresh_token);
        $this->assertTrue($connection->token_expires_at->equalTo($expiresAt));
    }

    public function test_it_dispatches_git_connection_established_event(): void
    {
        Event::fake();

        $connection = (new CreateGitConnection)->execute(
            GitProvider::Github,
            'access-token',
            null,
            null,
            'caleb',
        );

        Event::assertDispatched(GitConnectionEstablished::class, fn (GitConnectionEstablished $event) => $event->gitConnection->is($connection));
    }
}
