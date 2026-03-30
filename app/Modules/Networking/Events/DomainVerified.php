<?php

namespace App\Modules\Networking\Events;

use App\Modules\Networking\Models\Domain;

class DomainVerified
{
    public function __construct(public Domain $domain) {}
}
