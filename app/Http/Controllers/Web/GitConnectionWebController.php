<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\AppPlatform\Actions\CreateGitConnection;
use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Services\GitHubOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GitConnectionWebController extends Controller
{
    public function authorizeGithub(Request $request, GitHubOAuthService $gitHubOAuthService): Response
    {
        $state = Str::random(40);

        $request->session()->put('github_oauth_state', $state);
        $request->session()->put('github_oauth_redirect_to', $request->input('redirect_to', url()->previous()));

        return Inertia::location($gitHubOAuthService->authorizationUrl($state));
    }

    public function handleGithubCallback(Request $request, GitHubOAuthService $gitHubOAuthService): RedirectResponse
    {
        $expectedState = $request->session()->pull('github_oauth_state');

        if (! is_string($expectedState) || ! hash_equals($expectedState, (string) $request->query('state'))) {
            throw new AccessDeniedHttpException('Invalid OAuth state.');
        }

        $tokenData = $gitHubOAuthService->exchangeCode((string) $request->query('code'));
        $accountName = $gitHubOAuthService->fetchAccountName($tokenData->accessToken);

        (new CreateGitConnection)->execute(
            provider: GitProvider::Github,
            accessToken: $tokenData->accessToken,
            refreshToken: $tokenData->refreshToken,
            tokenExpiresAt: $tokenData->tokenExpiresAt,
            accountName: $accountName,
        );

        return redirect()->to((string) $request->session()->pull('github_oauth_redirect_to', route('projects.index')));
    }
}
