<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Runner API Routes
|--------------------------------------------------------------------------
|
| Routes for CI runner communication. Authenticated via per-runner
| bearer tokens, not user sessions.
|
*/

Route::prefix('api/runner')->middleware('throttle:120,1')->group(function () {
    // GET    /api/runner/jobs/next
    // PUT    /api/runner/jobs/{id}/status
    // POST   /api/runner/jobs/{id}/log
    // POST   /api/runner/jobs/{id}/artifact
    // POST   /api/runner/heartbeat
});
