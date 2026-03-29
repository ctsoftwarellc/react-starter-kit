<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\CreateSecret;
use App\Modules\AppPlatform\DTOs\CreateSecretData;
use App\Modules\AppPlatform\Events\SecretUpdated;
use App\Modules\AppPlatform\Models\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateSecretTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_secret_with_encrypted_value(): void
    {
        Event::fake();

        $environment = Environment::factory()->create();

        $secret = (new CreateSecret)->execute($environment, new CreateSecretData(
            key: 'APP_KEY',
            value: 'base64:test-secret',
        ));

        $this->assertDatabaseHas('secrets', [
            'id' => $secret->id,
            'environment_id' => $environment->id,
            'key' => 'APP_KEY',
            'version' => 1,
        ]);
        $this->assertNotSame('base64:test-secret', $secret->getRawOriginal('encrypted_value'));
        $this->assertSame('base64:test-secret', $secret->encrypted_value);
    }

    public function test_it_dispatches_secret_updated_event(): void
    {
        Event::fake();

        $secret = (new CreateSecret)->execute(Environment::factory()->create(), new CreateSecretData(
            key: 'APP_KEY',
            value: 'base64:test-secret',
        ));

        Event::assertDispatched(SecretUpdated::class, fn (SecretUpdated $event) => $event->secret?->is($secret) && $event->changeType === 'created');
    }
}
