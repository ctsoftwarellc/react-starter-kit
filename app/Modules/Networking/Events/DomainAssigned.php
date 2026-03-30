<?php

namespace App\Modules\Networking\Events;

use App\Modules\Networking\Models\Domain;

class DomainAssigned
{
    public function __construct(public Domain $domain) {}
}
