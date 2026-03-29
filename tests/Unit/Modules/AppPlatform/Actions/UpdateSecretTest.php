<?php

namespace Tests\Unit\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Actions\UpdateSecret;
use App\Modules\AppPlatform\DTOs\UpdateSecretData;
use App\Modules\AppPlatform\Models\Secret;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSecretTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rotates_secret_value_and_increments_version(): void
    {
        $secret = Secret::factory()->create([
            'encrypted_value' => 'old-secret',
            'version' => 1,
        ]);

        $updated = (new UpdateSecret)->execute($secret, new UpdateSecretData(
            value: 'new-secret',
            hasValue: true,
        ));

        $this->assertSame(2, $updated->version);
        $this->assertSame('new-secret', $updated->encrypted_value);
    }
}
