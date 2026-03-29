<?php

namespace App\Modules\AppPlatform\Events;

use App\Modules\AppPlatform\Models\Secret;

class SecretUpdated
{
    public function __construct(
        public ?Secret $secret,
        public string $changeType,
        public array $metadata = [],
    ) {}
}
