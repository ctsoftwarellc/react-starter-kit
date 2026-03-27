<?php

namespace Tests\Unit\Actions;

use App\Actions\CreatePersonalAccessToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePersonalAccessTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_token_record_with_hashed_value(): void
    {
        $user = User::factory()->create();

        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $result->token->id,
            'user_id' => $user->id,
            'name' => 'Test Token',
        ]);

        // Token stored in DB should be a sha256 hash, not the plain text
        $this->assertNotEquals($result->plainTextToken, $result->token->token);
    }

    public function test_it_returns_plaintext_token_that_hashes_to_stored_value(): void
    {
        $user = User::factory()->create();

        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        $this->assertEquals(
            hash('sha256', $result->plainTextToken),
            $result->token->token
        );
    }

    public function test_it_sets_default_abilities_to_wildcard(): void
    {
        $user = User::factory()->create();

        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        $this->assertEquals(['*'], $result->token->abilities);
    }

    public function test_it_handles_custom_expiry_date(): void
    {
        $user = User::factory()->create();
        $expiresAt = CarbonImmutable::now()->addDays(30);

        $result = (new CreatePersonalAccessToken)->execute(
            $user,
            'Test Token',
            ['*'],
            $expiresAt
        );

        $this->assertNotNull($result->token->expires_at);
        $this->assertTrue($result->token->expires_at->isSameDay($expiresAt));
    }
}
