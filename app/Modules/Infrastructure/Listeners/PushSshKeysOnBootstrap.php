<?php

namespace App\Modules\Infrastructure\Listeners;

use App\Modules\Infrastructure\Events\ServerBootstrapped;
use App\Modules\Infrastructure\Jobs\PushSshKeys;

class PushSshKeysOnBootstrap
{
    public function handle(ServerBootstrapped $event): void
    {
        PushSshKeys::dispatch($event->server);
    }
}
