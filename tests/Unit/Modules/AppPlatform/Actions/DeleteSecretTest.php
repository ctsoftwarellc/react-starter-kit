<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\DeleteSecret;
use App\Modules\AppPlatform\Events\SecretUpdated;
use App\Modules\AppPlatform\Models\Secret;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteSecretTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_secret_without_leaking_plaintext(): void
    {
        Event::fake();

        $secret = Secret::factory()->create([
            'key' => 'APP_KEY',
            'encrypted_value' => 'top-secret',
            'version' => 3,
        ]);

        (new DeleteSecret)->execute($secret);

        $this->assertDatabaseMissing('secrets', ['id' => $secret->id]);

        Event::assertDispatched(SecretUpdated::class, function (SecretUpdated $event) {
            return $event->changeType === 'deleted'
                && $event->metadata['key'] === 'APP_KEY'
                && ! in_array('top-secret', $event->metadata, true);
        });
    }
}
