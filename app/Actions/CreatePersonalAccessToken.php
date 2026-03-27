<?php

namespace App\Actions;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class CreatePersonalAccessToken
{
    public function execute(
        User $user,
        string $name,
        array $abilities = ['*'],
        ?CarbonImmutable $expiresAt = null,
    ): object {
        $plainText = Str::random(64);

        $token = PersonalAccessToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plainText),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return (object) [
            'token' => $token,
            'plainTextToken' => $plainText,
        ];
    }
}
