<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $hashedToken = hash('sha256', $bearer);

        $token = PersonalAccessToken::where('token', $hashedToken)->first();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->update(['last_used_at' => now()]);

        Auth::setUser($token->user);

        return $next($request);
    }
}
