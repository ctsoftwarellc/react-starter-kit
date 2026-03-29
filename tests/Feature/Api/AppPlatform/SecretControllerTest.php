<?php

namespace Tests\Feature\Api\AppPlatform;

use App\Models\User;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\Operations\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_secrets_without_plaintext_values(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        Secret::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'APP_KEY',
            'encrypted_value' => 'top-secret',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/environments/'.$environment->id.'/secrets');

        $response->assertOk()
            ->assertJsonPath('data.0.key', 'APP_KEY')
            ->assertJsonMissingPath('data.0.value')
            ->assertJsonMissingPath('data.0.encrypted_value');
        $this->assertStringNotContainsString('top-secret', $response->getContent());
    }

    public function test_can_create_secret(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/secrets', [
                'key' => 'APP_KEY',
                'value' => 'top-secret',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'APP_KEY')
            ->assertJsonMissingPath('data.value');
        $this->assertStringNotContainsString('top-secret', $response->getContent());
    }

    public function test_can_update_secret(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $secret = Secret::factory()->create(['environment_id' => $environment->id, 'key' => 'OLD_KEY', 'encrypted_value' => 'old']);

        $response = $this->actingAs($user)
            ->putJson('/api/v1/environments/'.$environment->id.'/secrets/'.$secret->id, [
                'key' => 'NEW_KEY',
                'value' => 'new-secret',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.key', 'NEW_KEY')
            ->assertJsonMissingPath('data.value');
        $this->assertStringNotContainsString('new-secret', $response->getContent());
    }

    public function test_can_delete_secret(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $secret = Secret::factory()->create(['environment_id' => $environment->id]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/environments/'.$environment->id.'/secrets/'.$secret->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('secrets', ['id' => $secret->id]);
    }

    public function test_can_reveal_secret_explicitly(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $secret = Secret::factory()->create(['environment_id' => $environment->id, 'encrypted_value' => 'top-secret']);

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id.'/secrets/'.$secret->id.'/reveal')
            ->assertOk()
            ->assertJsonPath('value', 'top-secret');
    }

    public function test_normal_secret_endpoints_never_leak_plaintext(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        Secret::factory()->create(['environment_id' => $environment->id, 'encrypted_value' => 'hidden-value']);

        $responses = [
            $this->actingAs($user)->getJson('/api/v1/environments/'.$environment->id.'/secrets'),
            $this->actingAs($user)->postJson('/api/v1/environments/'.$environment->id.'/secrets', [
                'key' => 'NEW_SECRET',
                'value' => 'super-secret',
            ]),
        ];

        foreach ($responses as $response) {
            $this->assertStringNotContainsString('hidden-value', $response->getContent());
            $this->assertStringNotContainsString('super-secret', $response->getContent());
        }
    }

    public function test_duplicate_secret_key_within_environment_returns_422(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        Secret::factory()->create(['environment_id' => $environment->id, 'key' => 'APP_KEY']);

        $this->actingAs($user)
            ->postJson('/api/v1/environments/'.$environment->id.'/secrets', [
                'key' => 'APP_KEY',
                'value' => 'another-secret',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['key']);
    }

    public function test_revealing_secret_records_audit_log(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $environment = Environment::factory()->create();
        $secret = Secret::factory()->create([
            'environment_id' => $environment->id,
            'key' => 'APP_KEY',
            'encrypted_value' => 'top-secret',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/environments/'.$environment->id.'/secrets/'.$secret->id.'/reveal')
            ->assertOk();

        $auditLog = AuditLog::query()->latest()->first();

        $this->assertSame('secret.revealed', $auditLog->action);
        $this->assertSame($user->id, $auditLog->user_id);
        $this->assertStringNotContainsString('top-secret', json_encode($auditLog->new_values));
    }
}
