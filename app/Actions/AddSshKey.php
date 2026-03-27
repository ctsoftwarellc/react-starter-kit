<?php

namespace App\Actions;

use App\Models\SshKey;
use App\Models\User;
use InvalidArgumentException;

class AddSshKey
{
    private const ALLOWED_KEY_TYPES = [
        'ssh-rsa',
        'ssh-ed25519',
        'ecdsa-sha2-nistp256',
        'ecdsa-sha2-nistp384',
        'ecdsa-sha2-nistp521',
        'sk-ssh-ed25519',
    ];

    public function execute(User $user, string $name, string $publicKey): SshKey
    {
        $parts = preg_split('/\s+/', trim($publicKey));

        if (count($parts) < 2) {
            throw new InvalidArgumentException('Invalid SSH public key format.');
        }

        $keyType = $parts[0];
        $keyData = $parts[1];

        if (! in_array($keyType, self::ALLOWED_KEY_TYPES, true)) {
            throw new InvalidArgumentException("Unsupported SSH key type: {$keyType}");
        }

        $decoded = base64_decode($keyData, true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid SSH public key: unable to decode key data.');
        }

        $fingerprint = 'SHA256:'.base64_encode(hash('sha256', $decoded, true));

        return SshKey::create([
            'user_id' => $user->id,
            'name' => $name,
            'public_key' => trim($publicKey),
            'fingerprint' => $fingerprint,
        ]);
    }
}
