<?php

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
    // POST   /webhooks/{application}/{provider}
});
