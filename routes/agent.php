<?php

use App\Http\Controllers\Api\Agent\AgentController;
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

Route::prefix('api/agent')->middleware(['throttle:120,1', 'auth.agent'])->group(function () {
    Route::post('heartbeat', [AgentController::class, 'heartbeat'])->name('agent.heartbeat');
    Route::get('commands/pending', [AgentController::class, 'pendingCommands'])->name('agent.commands.pending');
    Route::post('commands/{agentCommand}/result', [AgentController::class, 'reportResult'])->name('agent.commands.result');
});
