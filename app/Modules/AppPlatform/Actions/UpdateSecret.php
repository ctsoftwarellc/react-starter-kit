<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\UpdateSecretData;
use App\Modules\AppPlatform\Events\SecretUpdated;
use App\Modules\AppPlatform\Models\Secret;

class UpdateSecret
{
    public function execute(Secret $secret, UpdateSecretData $data): Secret
    {
        $attributes = [];

        if ($data->hasKey) {
            $attributes['key'] = $data->key;
        }

        if ($data->hasValue) {
            $attributes['encrypted_value'] = $data->value;
            $attributes['version'] = $secret->version + 1;
        }

        $secret->update($attributes);

        $secret = $secret->fresh();

        event(new SecretUpdated($secret, 'updated', [
            'key' => $secret->key,
            'version' => $secret->version,
        ]));

        return $secret;
    }
}
