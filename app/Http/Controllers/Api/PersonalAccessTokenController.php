<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreatePersonalAccessToken;
use App\Actions\RevokePersonalAccessToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTokenRequest;
use App\Http\Resources\PersonalAccessTokenResource;
use App\Models\PersonalAccessToken;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PersonalAccessTokenController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tokens = $request->user()
            ->tokens()
            ->latest()
            ->paginate(15);

        return PersonalAccessTokenResource::collection($tokens);
    }

    public function store(CreateTokenRequest $request): PersonalAccessTokenResource
    {
        $result = (new CreatePersonalAccessToken)->execute(
            user: $request->user(),
            name: $request->validated('name'),
            abilities: $request->validated('abilities', ['*']),
            expiresAt: $request->validated('expires_at')
                ? CarbonImmutable::parse($request->validated('expires_at'))
                : null,
        );

        return (new PersonalAccessTokenResource($result->token))
            ->additional(['plain_text_token' => $result->plainTextToken]);
    }

    public function destroy(PersonalAccessToken $token): Response
    {
        (new RevokePersonalAccessToken)->execute($token);

        return response()->noContent();
    }
}
