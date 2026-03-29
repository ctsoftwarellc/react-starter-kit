<?php

namespace App\Modules\AppPlatform\Actions;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Events\GitConnectionEstablished;
use App\Modules\AppPlatform\Models\GitConnection;
use Carbon\CarbonInterface;

class CreateGitConnection
{
    public function execute(
        GitProvider $provider,
        string $accessToken,
        ?string $refreshToken,
        ?CarbonInterface $tokenExpiresAt,
        string $accountName,
    ): GitConnection {
        $connection = GitConnection::updateOrCreate(
            [
                'provider' => $provider->value,
                'account_name' => $accountName,
            ],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expires_at' => $tokenExpiresAt,
            ],
        );

        event(new GitConnectionEstablished($connection));

        return $connection;
    }
}
