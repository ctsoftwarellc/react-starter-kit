<?php

namespace App\Modules\Infrastructure\Events;

use App\Modules\Infrastructure\Models\Provider;

class ProviderCreated
{
    public function __construct(public Provider $provider) {}
}
