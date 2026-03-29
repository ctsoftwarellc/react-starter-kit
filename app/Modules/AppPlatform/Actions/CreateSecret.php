<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\DTOs\CreateSecretData;
use App\Modules\AppPlatform\Events\SecretUpdated;
use App\Modules\AppPlatform\Models\Environment;
use App\Modules\AppPlatform\Models\Secret;

class CreateSecret
{
    public function execute(Environment $environment, CreateSecretData $data): Secret
    {
        $secret = Secret::create([
            'environment_id' => $environment->id,
            'key' => $data->key,
            'encrypted_value' => $data->value,
            'version' => 1,
        ]);

        event(new SecretUpdated($secret, 'created', [
            'key' => $secret->key,
            'version' => $secret->version,
        ]));

        return $secret;
    }
}
