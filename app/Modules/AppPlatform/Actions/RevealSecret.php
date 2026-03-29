<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Models\Secret;
use App\Modules\Operations\Actions\RecordAuditLog;

class RevealSecret
{
    public function execute(Secret $secret): string
    {
        (new RecordAuditLog)->execute(
            action: 'secret.revealed',
            auditable: $secret,
            oldValues: null,
            newValues: [
                'key' => $secret->key,
                'version' => $secret->version,
            ],
        );

        return $secret->encrypted_value;
    }
}
