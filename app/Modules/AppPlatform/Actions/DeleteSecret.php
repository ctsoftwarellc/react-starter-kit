<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Events\SecretUpdated;
use App\Modules\AppPlatform\Models\Secret;

class DeleteSecret
{
    public function execute(Secret $secret): void
    {
        $metadata = [
            'key' => $secret->key,
            'version' => $secret->version,
        ];

        $secret->delete();

        event(new SecretUpdated($secret, 'deleted', $metadata));
    }
}
