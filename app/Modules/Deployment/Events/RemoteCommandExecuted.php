<?php

namespace App\Modules\Deployment\Events;

use App\Modules\Deployment\Models\RemoteCommand;

class RemoteCommandExecuted
{
    public function __construct(public RemoteCommand $remoteCommand) {}
}
