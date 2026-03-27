<?php

namespace Tests\Feature\Api;

use App\Actions\CreatePersonalAccessToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalAccessTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(): array
    {
        $user = User::factory()->create();
        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        return [$user, $result->plainTextToken];
    }

    public function test_list_tokens_returns_200(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/tokens');

        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'name', 'abilities']]]);
    }

    public function test_create_token_returns_201_with_plain_text_token(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/tokens', [
                'name' => 'New Token',
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['id', 'name'], 'plain_text_token']);
        $this->assertNotEmpty($response->json('plain_text_token'));
    }

    public function test_revoke_token_returns_204(): void
    {
        [$user, $token] = $this->createUserWithToken();

        // Create a second token to revoke
        $result = (new CreatePersonalAccessToken)->execute($user, 'Token to Revoke');
        $tokenToRevoke = $result->token;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/tokens/'.$tokenToRevoke->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenToRevoke->id]);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/tokens');

        $response->assertUnauthorized();
    }

    public function test_api_auth_via_bearer_token_works(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/tokens');

        $response->assertOk();
    }

    public function test_expired_token_returns_401(): void
    {
        $user = User::factory()->create();

        $result = (new CreatePersonalAccessToken)->execute(
            $user,
            'Expired Token',
            ['*'],
            CarbonImmutable::now()->subDay(),
        );

        // Manually set expires_at to the past since validation prevents it via the request
        $result->token->update(['expires_at' => CarbonImmutable::now()->subDay()]);

        $response = $this->withHeader('Authorization', 'Bearer '.$result->plainTextToken)
            ->getJson('/api/v1/tokens');

        $response->assertUnauthorized();
    }
}
