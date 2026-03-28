<?php

namespace App\Modules\Infrastructure\Events;

use App\Modules\Infrastructure\Models\Provider;

class ProviderUpdated
{
    public function __construct(public Provider $provider) {}
}
