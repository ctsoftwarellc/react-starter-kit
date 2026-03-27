<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Phase 1: Auth & Projects
    // Route::apiResource('projects', ...);
    // Route::apiResource('ssh-keys', ...);
    // Route::apiResource('tokens', ...);

    // Phase 2: Infrastructure
    // Route::apiResource('providers', ...);
    // Route::apiResource('servers', ...);
    // Route::apiResource('clusters', ...);

    // Phase 3: Applications
    // Route::apiResource('applications', ...);

    // Phase 4: Pipelines
    // Route::apiResource('pipelines', ...);

    // Phase 5: Deployments
    // Route::apiResource('deployments', ...);
});
