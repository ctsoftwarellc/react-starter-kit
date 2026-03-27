<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Agent API Routes
|--------------------------------------------------------------------------
|
| Routes for node agent communication. Authenticated via per-server
| bearer tokens, not user sessions.
|
*/

Route::prefix('api/agent')->middleware('throttle:120,1')->group(function () {
    // POST   /api/agent/heartbeat
    // GET    /api/agent/commands/pending
    // POST   /api/agent/commands/{id}/result
    // POST   /api/agent/commands/{id}/log
});
