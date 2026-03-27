<?php

namespace App\Actions;

use App\Models\PersonalAccessToken;

class RevokePersonalAccessToken
{
    public function execute(PersonalAccessToken $token): void
    {
        $token->delete();
    }
}
