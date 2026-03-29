<?php

namespace App\Modules\AppPlatform\Services;

use App\Modules\AppPlatform\DTOs\GitHubTokenData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class GitHubOAuthService
{
    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.github.client_id'),
            'redirect_uri' => config('services.github.redirect'),
            'scope' => 'repo read:user user:email admin:repo_hook',
            'state' => $state,
        ]);

        return 'https://github.com/login/oauth/authorize?'.$query;
    }

    public function exchangeCode(string $code): GitHubTokenData
    {
        $response = Http::asForm()
            ->acceptJson()
            ->post('https://github.com/login/oauth/access_token', [
                'client_id' => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'redirect_uri' => config('services.github.redirect'),
                'code' => $code,
            ])
            ->throw()
            ->json();

        if (! isset($response['access_token'])) {
            throw new InvalidArgumentException('GitHub OAuth response missing access token.');
        }

        return new GitHubTokenData(
            accessToken: $response['access_token'],
            refreshToken: $response['refresh_token'] ?? null,
            tokenExpiresAt: isset($response['expires_in'])
                ? CarbonImmutable::now()->addSeconds((int) $response['expires_in'])
                : null,
        );
    }

    public function fetchAccountName(string $accessToken): string
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://api.github.com/user')
            ->throw()
            ->json();

        $accountName = $response['login'] ?? null;

        if (! is_string($accountName) || $accountName === '') {
            throw new InvalidArgumentException('GitHub account name could not be resolved.');
        }

        return $accountName;
    }
}
