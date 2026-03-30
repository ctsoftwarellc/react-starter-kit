<?php

namespace App\Http\Middleware;

use App\Modules\Pipeline\Models\Runner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRunner
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $runner = Runner::query()->where('token_hash', hash('sha256', $bearer))->first();

        if (! $runner) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->attributes->set('runner', $runner);

        return $next($request);
    }
}
