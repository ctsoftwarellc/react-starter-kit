<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Models\User;
use App\Modules\AppPlatform\Actions\RevealSecret;
use App\Modules\AppPlatform\Models\Secret;
use App\Modules\Operations\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevealSecretTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_decrypted_secret_value(): void
    {
        $secret = Secret::factory()->create(['encrypted_value' => 'top-secret']);

        $value = (new RevealSecret)->execute($secret);

        $this->assertSame('top-secret', $value);
    }

    public function test_it_records_audit_log_for_reveal(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $secret = Secret::factory()->create(['key' => 'APP_KEY', 'encrypted_value' => 'top-secret', 'version' => 2]);

        $this->actingAs($user);

        (new RevealSecret)->execute($secret);

        $auditLog = AuditLog::query()->latest()->first();

        $this->assertNotNull($auditLog);
        $this->assertSame('secret.revealed', $auditLog->action);
        $this->assertSame($user->id, $auditLog->user_id);
        $this->assertSame('APP_KEY', $auditLog->new_values['key']);
        $this->assertArrayNotHasKey('value', $auditLog->new_values);
        $this->assertStringNotContainsString('top-secret', json_encode($auditLog->new_values));
    }
}
