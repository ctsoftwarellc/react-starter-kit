<?php

use App\Http\Controllers\Api\AppPlatform\ProjectController;
use App\Http\Controllers\Api\Infrastructure\ClusterController;
use App\Http\Controllers\Api\Infrastructure\ProviderController;
use App\Http\Controllers\Api\Infrastructure\ServerController;
use App\Http\Controllers\Api\Operations\AuditLogController;
use App\Http\Controllers\Api\PersonalAccessTokenController;
use App\Http\Controllers\Api\SshKeyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.token')->prefix('v1')->name('api.')->group(function () {
    Route::apiResource('tokens', PersonalAccessTokenController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('ssh-keys', SshKeyController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('projects', ProjectController::class);
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Infrastructure
    Route::apiResource('providers', ProviderController::class);
    Route::post('providers/{provider}/test', [ProviderController::class, 'test'])->name('providers.test');

    Route::apiResource('servers', ServerController::class);
    Route::post('servers/{server}/bootstrap', [ServerController::class, 'bootstrap'])->name('servers.bootstrap');
    Route::post('servers/{server}/drain', [ServerController::class, 'drain'])->name('servers.drain');
    Route::post('servers/{server}/cordon', [ServerController::class, 'cordon'])->name('servers.cordon');
    Route::post('servers/{server}/activate', [ServerController::class, 'activate'])->name('servers.activate');

    Route::apiResource('clusters', ClusterController::class);
    Route::post('clusters/{cluster}/nodes', [ClusterController::class, 'addNode'])->name('clusters.add-node');
    Route::delete('clusters/{cluster}/nodes/{server}', [ClusterController::class, 'removeNode'])->name('clusters.remove-node');
    Route::put('clusters/{cluster}/nodes/{server}', [ClusterController::class, 'updateNode'])->name('clusters.update-node');
});
