<?php

use App\Http\Controllers\Api\AppPlatform\ProjectController;
use App\Http\Controllers\Api\Operations\AuditLogController;
use App\Http\Controllers\Api\PersonalAccessTokenController;
use App\Http\Controllers\Api\SshKeyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.token')->prefix('v1')->name('api.')->group(function () {
    Route::apiResource('tokens', PersonalAccessTokenController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('ssh-keys', SshKeyController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('projects', ProjectController::class);
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});
