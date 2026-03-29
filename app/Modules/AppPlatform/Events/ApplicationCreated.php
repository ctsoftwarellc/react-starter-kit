<?php

namespace App\Modules\AppPlatform\Events;

use App\Modules\AppPlatform\Models\Application;

class ApplicationCreated
{
    public function __construct(
        public Application $application,
        public ?string $defaultClusterId = null,
    ) {}
}
