<?php

namespace App\Modules\Infrastructure\Events;

use App\Modules\Infrastructure\Models\Provider;

class ProviderDeleted
{
    public function __construct(public Provider $provider) {}
}
