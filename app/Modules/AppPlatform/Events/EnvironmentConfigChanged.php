<?php

namespace App\Modules\AppPlatform\Events;

use App\Modules\AppPlatform\Models\Environment;

class EnvironmentConfigChanged
{
    public function __construct(
        public Environment $environment,
        public string $changeType,
        public array $changes = [],
    ) {}
}
