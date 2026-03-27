<?php

namespace App\Actions;

use App\Models\SshKey;

class RemoveSshKey
{
    public function execute(SshKey $key): void
    {
        $key->delete();
    }
}
