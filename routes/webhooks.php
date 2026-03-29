<?php

use App\Http\Controllers\Api\Pipeline\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Incoming webhooks from git providers (GitHub, GitLab, Bitbucket).
| No session auth — verified via HMAC signature.
|
*/

Route::prefix('webhooks')->group(function () {
    Route::post('{application}/{provider}', [WebhookController::class, 'handle'])
        ->middleware('webhook.signature')
        ->name('webhooks.handle');
});
