<?php

namespace App\Modules\AppPlatform\Events;

use App\Modules\AppPlatform\Models\GitConnection;

class GitConnectionEstablished
{
    public function __construct(public GitConnection $gitConnection) {}
}
