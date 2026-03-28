<?php

namespace App\Http\Middleware;

use App\Modules\Infrastructure\Models\Server;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $hashedToken = hash('sha256', $bearer);

        $server = Server::where('agent_token_hash', $hashedToken)->first();

        if (! $server) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->merge(['server' => $server]);

        return $next($request);
    }
}
