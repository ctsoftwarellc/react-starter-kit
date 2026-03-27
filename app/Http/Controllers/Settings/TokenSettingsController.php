<?php

namespace App\Http\Controllers\Settings;

use App\Actions\CreatePersonalAccessToken;
use App\Actions\RevokePersonalAccessToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTokenRequest;
use App\Models\PersonalAccessToken;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TokenSettingsController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('settings/tokens', [
            'tokens' => $request->user()->tokens()->latest()->get(),
        ]);
    }

    public function store(CreateTokenRequest $request): RedirectResponse
    {
        $result = (new CreatePersonalAccessToken)->execute(
            user: $request->user(),
            name: $request->validated('name'),
            abilities: $request->validated('abilities', ['*']),
            expiresAt: $request->validated('expires_at')
                ? CarbonImmutable::parse($request->validated('expires_at'))
                : null,
        );

        return back()->with('plainTextToken', $result->plainTextToken);
    }

    public function destroy(PersonalAccessToken $personalAccessToken): RedirectResponse
    {
        (new RevokePersonalAccessToken)->execute($personalAccessToken);

        return back();
    }
}
