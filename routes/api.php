<?php

use App\Http\Controllers\Api\AppPlatform\ApplicationController;
use App\Http\Controllers\Api\AppPlatform\EnvironmentController;
use App\Http\Controllers\Api\AppPlatform\EnvironmentVariableController;
use App\Http\Controllers\Api\AppPlatform\GitConnectionController;
use App\Http\Controllers\Api\AppPlatform\ProcessDefinitionController;
use App\Http\Controllers\Api\AppPlatform\ProjectController;
use App\Http\Controllers\Api\AppPlatform\SecretController;
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
    Route::get('git-connections', [GitConnectionController::class, 'index'])->name('git-connections.index');
    Route::delete('git-connections/{gitConnection}', [GitConnectionController::class, 'destroy'])->name('git-connections.destroy');
    Route::get('projects/{project}/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::post('projects/{project}/applications', [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::put('applications/{application}', [ApplicationController::class, 'update'])->name('applications.update');
    Route::delete('applications/{application}', [ApplicationController::class, 'destroy'])->name('applications.destroy');
    Route::get('applications/{application}/environments', [EnvironmentController::class, 'index'])->name('environments.index');
    Route::post('applications/{application}/environments', [EnvironmentController::class, 'store'])->name('environments.store');
    Route::get('environments/{environment}', [EnvironmentController::class, 'show'])->name('environments.show');
    Route::put('environments/{environment}', [EnvironmentController::class, 'update'])->name('environments.update');
    Route::delete('environments/{environment}', [EnvironmentController::class, 'destroy'])->name('environments.destroy');
    Route::get('environments/{environment}/variables', [EnvironmentVariableController::class, 'index'])->name('environment-variables.index');
    Route::post('environments/{environment}/variables', [EnvironmentVariableController::class, 'store'])->name('environment-variables.store');
    Route::put('environments/{environment}/variables/{variable}', [EnvironmentVariableController::class, 'update'])->name('environment-variables.update');
    Route::delete('environments/{environment}/variables/{variable}', [EnvironmentVariableController::class, 'destroy'])->name('environment-variables.destroy');
    Route::get('environments/{environment}/secrets', [SecretController::class, 'index'])->name('secrets.index');
    Route::post('environments/{environment}/secrets', [SecretController::class, 'store'])->name('secrets.store');
    Route::put('environments/{environment}/secrets/{secret}', [SecretController::class, 'update'])->name('secrets.update');
    Route::delete('environments/{environment}/secrets/{secret}', [SecretController::class, 'destroy'])->name('secrets.destroy');
    Route::get('environments/{environment}/secrets/{secret}/reveal', [SecretController::class, 'reveal'])->name('secrets.reveal');
    Route::get('environments/{environment}/processes', [ProcessDefinitionController::class, 'index'])->name('processes.index');
    Route::post('environments/{environment}/processes', [ProcessDefinitionController::class, 'store'])->name('processes.store');
    Route::put('environments/{environment}/processes/{processDefinition}', [ProcessDefinitionController::class, 'update'])->name('processes.update');
    Route::delete('environments/{environment}/processes/{processDefinition}', [ProcessDefinitionController::class, 'destroy'])->name('processes.destroy');
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
