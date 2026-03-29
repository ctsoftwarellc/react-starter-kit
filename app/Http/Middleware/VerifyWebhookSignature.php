<?php

namespace App\Http\Middleware;

use App\Modules\AppPlatform\Enums\GitProvider;
use App\Modules\AppPlatform\Models\Application;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $application = $request->route('application');

        if (! $application instanceof Application) {
            abort(404);
        }

        $provider = (string) $request->route('provider');

        $webhook = $application->webhooks()
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();

        if ($webhook === null) {
            abort(404);
        }

        $signature = $this->signatureFromRequest($request, $provider);

        if (! is_string($signature) || $signature === '') {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $webhook->secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        return $next($request);
    }

    private function signatureFromRequest(Request $request, string $provider): ?string
    {
        return match ($provider) {
            GitProvider::Github->value => $request->header('X-Hub-Signature-256'),
            default => $request->header('X-Hub-Signature-256'),
        };
    }
}
