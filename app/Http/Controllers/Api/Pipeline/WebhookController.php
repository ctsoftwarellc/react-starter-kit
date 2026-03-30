<?php

namespace App\Http\Controllers\Api\Pipeline;

use App\Http\Controllers\Controller;
use App\Modules\AppPlatform\Models\Application;
use App\Modules\Pipeline\Jobs\ProcessWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request, Application $application, string $provider): JsonResponse
    {
        ProcessWebhook::dispatch(
            $application,
            $provider,
            $request->all(),
            $request->headers->all(),
        );

        return response()->json([
            'message' => 'Webhook received.',
            'application_id' => $application->id,
            'provider' => $provider,
        ], 202);
    }
}
