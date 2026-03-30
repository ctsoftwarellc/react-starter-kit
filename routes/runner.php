<?php

use App\Http\Controllers\Api\Runner\RunnerController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/runner')->middleware(['throttle:120,1', 'auth.runner'])->group(function () {
    Route::get('jobs/next', [RunnerController::class, 'nextJob']);
    Route::put('jobs/{pipelineJob}/status', [RunnerController::class, 'updateStatus']);
    Route::post('jobs/{pipelineJob}/log', [RunnerController::class, 'appendLog']);
    Route::post('jobs/{pipelineJob}/artifact', [RunnerController::class, 'uploadArtifact']);
    Route::post('heartbeat', [RunnerController::class, 'heartbeat']);
});
