<?php

namespace App\Http\Controllers\Api\Pipeline;

use App\Http\Controllers\Controller;
use App\Modules\AppPlatform\Models\Application;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function handle(Application $application, string $provider): JsonResponse
    {
        return response()->json([
            'message' => 'Webhook received.',
            'application_id' => $application->id,
            'provider' => $provider,
        ], 202);
    }
}
