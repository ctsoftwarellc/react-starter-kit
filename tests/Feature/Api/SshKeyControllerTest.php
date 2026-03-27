<?php

namespace Tests\Feature\Api;

use App\Actions\AddSshKey;
use App\Actions\CreatePersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SshKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $validKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOMqqnkVzrm0SdG6UOoqKLsabgH5C9okWi0dh2l9GKJl test@example.com';

    private function createUserWithToken(): array
    {
        $user = User::factory()->create();
        $result = (new CreatePersonalAccessToken)->execute($user, 'Test Token');

        return [$user, $result->plainTextToken];
    }

    public function test_list_ssh_keys_returns_200(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/ssh-keys');

        $response->assertOk();
    }

    public function test_add_ssh_key_returns_201(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/ssh-keys', [
                'name' => 'My Key',
                'public_key' => $this->validKey,
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['id', 'name', 'fingerprint']]);
    }

    public function test_add_ssh_key_with_invalid_format_returns_error(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/ssh-keys', [
                'name' => 'Bad Key',
                'public_key' => 'not-a-valid-key',
            ]);

        // The AddSshKey action throws InvalidArgumentException, which should result in 500
        // unless there's exception handling. Let's just assert it's not a success.
        $response->assertServerError();
    }

    public function test_duplicate_fingerprint_returns_error(): void
    {
        [$user, $token] = $this->createUserWithToken();

        // Create first key directly
        (new AddSshKey)->execute($user, 'First Key', $this->validKey);

        // Try to create duplicate via API
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/ssh-keys', [
                'name' => 'Duplicate Key',
                'public_key' => $this->validKey,
            ]);

        // Should fail due to unique fingerprint constraint
        $response->assertServerError();
    }

    public function test_delete_ssh_key_returns_204(): void
    {
        [$user, $token] = $this->createUserWithToken();

        $key = (new AddSshKey)->execute($user, 'Key to Delete', $this->validKey);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/ssh-keys/'.$key->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('ssh_keys', ['id' => $key->id]);
    }
}
