<?php

namespace App\Modules\AppPlatform\DTOs;

use Carbon\CarbonInterface;

class GitHubTokenData
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken = null,
        public ?CarbonInterface $tokenExpiresAt = null,
    ) {}
}
