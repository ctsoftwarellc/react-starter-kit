<?php

namespace Tests\Unit\Actions;

use App\Actions\AddSshKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AddSshKeyTest extends TestCase
{
    use RefreshDatabase;

    private string $validKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOMqqnkVzrm0SdG6UOoqKLsabgH5C9okWi0dh2l9GKJl test@example.com';

    public function test_it_creates_ssh_key_record(): void
    {
        $user = User::factory()->create();

        $key = (new AddSshKey)->execute($user, 'My Key', $this->validKey);

        $this->assertDatabaseHas('ssh_keys', [
            'id' => $key->id,
            'user_id' => $user->id,
            'name' => 'My Key',
        ]);
        $this->assertEquals(trim($this->validKey), $key->public_key);
    }

    public function test_it_computes_fingerprint_consistently_for_same_key(): void
    {
        $user = User::factory()->create();

        $key1 = (new AddSshKey)->execute($user, 'Key 1', $this->validKey);

        // Delete the first key so fingerprint uniqueness constraint doesn't block
        $key1->delete();

        $key2 = (new AddSshKey)->execute($user, 'Key 2', $this->validKey);

        $this->assertEquals($key1->fingerprint, $key2->fingerprint);
        $this->assertStringStartsWith('SHA256:', $key1->fingerprint);
    }

    public function test_it_rejects_invalid_public_key_format(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        (new AddSshKey)->execute($user, 'Bad Key', 'not-a-valid-key');
    }

    public function test_it_rejects_unsupported_key_type(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported SSH key type');

        (new AddSshKey)->execute($user, 'Bad Key', 'ssh-dss AAAAB3NzaC1kc3MAAACB test@example.com');
    }

    public function test_it_rejects_invalid_base64_key_data(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unable to decode');

        (new AddSshKey)->execute($user, 'Bad Key', 'ssh-ed25519 !!!invalid-base64!!! test@example.com');
    }
}
