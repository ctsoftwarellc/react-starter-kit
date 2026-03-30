<?php

use App\Http\Controllers\Web\ActivityWebController;
use App\Http\Controllers\Web\ApplicationWebController;
use App\Http\Controllers\Web\ClusterWebController;
use App\Http\Controllers\Web\DeploymentWebController;
use App\Http\Controllers\Web\EnvironmentWebController;
use App\Http\Controllers\Web\GitConnectionWebController;
use App\Http\Controllers\Web\PipelineRunWebController;
use App\Http\Controllers\Web\ProjectWebController;
use App\Http\Controllers\Web\RunnerWebController;
use App\Http\Controllers\Web\ServerWebController;
use App\Http\Controllers\Web\ServiceManagementWebController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::post('git-connections/github/authorize', [GitConnectionWebController::class, 'authorizeGithub'])->name('git-connections.github.authorize');
    Route::get('git-connections/github/callback', [GitConnectionWebController::class, 'handleGithubCallback'])->name('git-connections.github.callback');

    Route::resource('projects', ProjectWebController::class);

    Route::scopeBindings()->group(function () {
        Route::get('projects/{project}/applications/create', [ApplicationWebController::class, 'create'])->name('applications.create');
        Route::post('projects/{project}/applications', [ApplicationWebController::class, 'store'])->name('applications.store');
        Route::get('applications/{application}', [ApplicationWebController::class, 'show'])->name('applications.show');
        Route::put('applications/{application}', [ApplicationWebController::class, 'update'])->name('applications.update');
        Route::delete('applications/{application}', [ApplicationWebController::class, 'destroy'])->name('applications.destroy');
        Route::post('applications/{application}/environments', [EnvironmentWebController::class, 'store'])->name('environments.store');
        Route::get('environments/{environment}', [EnvironmentWebController::class, 'show'])->name('environments.show');
        Route::put('environments/{environment}', [EnvironmentWebController::class, 'update'])->name('environments.update');
        Route::delete('environments/{environment}', [EnvironmentWebController::class, 'destroy'])->name('environments.destroy');
        Route::post('environments/{environment}/deploy', [EnvironmentWebController::class, 'deploy'])->name('environments.deploy');
        Route::post('environments/{environment}/rollback', [EnvironmentWebController::class, 'rollback'])->name('environments.rollback');
        Route::put('environments/{environment}/health-check', [EnvironmentWebController::class, 'upsertHealthCheck'])->name('environments.health-check');
        Route::post('environments/{environment}/runtime-profiles', [EnvironmentWebController::class, 'storeRuntimeProfile'])->name('environments.runtime-profiles.store');
        Route::put('environments/{environment}/runtime-profiles/{runtimeProfile}', [EnvironmentWebController::class, 'updateRuntimeProfile'])->name('environments.runtime-profiles.update');
        Route::post('environments/{environment}/runtime-profile/apply', [EnvironmentWebController::class, 'applyRuntimeProfile'])->name('environments.runtime-profiles.apply');
        Route::post('environments/{environment}/server-role-profiles', [EnvironmentWebController::class, 'upsertServerRoleProfile'])->name('environments.server-role-profiles.store');
        Route::put('environments/{environment}/server-role-profiles/{serverRoleProfile}', [EnvironmentWebController::class, 'updateServerRoleProfile'])->name('environments.server-role-profiles.update');
        Route::post('environments/{environment}/remote-commands', [EnvironmentWebController::class, 'executeRemoteCommand'])->name('environments.remote-commands.store');
        Route::post('environments/{environment}/variables', [EnvironmentWebController::class, 'storeVariable'])->name('environment-variables.store');
        Route::put('environments/{environment}/variables/{variable}', [EnvironmentWebController::class, 'updateVariable'])->name('environment-variables.update');
        Route::delete('environments/{environment}/variables/{variable}', [EnvironmentWebController::class, 'destroyVariable'])->name('environment-variables.destroy');
        Route::post('environments/{environment}/secrets', [EnvironmentWebController::class, 'storeSecret'])->name('environment-secrets.store');
        Route::put('environments/{environment}/secrets/{secret}', [EnvironmentWebController::class, 'updateSecret'])->name('environment-secrets.update');
        Route::delete('environments/{environment}/secrets/{secret}', [EnvironmentWebController::class, 'destroySecret'])->name('environment-secrets.destroy');
        Route::get('environments/{environment}/secrets/{secret}/reveal', [EnvironmentWebController::class, 'revealSecret'])->name('environment-secrets.reveal');
        Route::post('environments/{environment}/processes', [EnvironmentWebController::class, 'storeProcess'])->name('environment-processes.store');
        Route::put('environments/{environment}/processes/{processDefinition}', [EnvironmentWebController::class, 'updateProcess'])->name('environment-processes.update');
        Route::delete('environments/{environment}/processes/{processDefinition}', [EnvironmentWebController::class, 'destroyProcess'])->name('environment-processes.destroy');
        Route::post('service-management/databases', [ServiceManagementWebController::class, 'storeDatabase'])->name('service-management.databases.store');
        Route::delete('service-management/databases/{databaseInstance}', [ServiceManagementWebController::class, 'destroyDatabase'])->name('service-management.databases.destroy');
        Route::post('service-management/databases/{databaseInstance}/rotate-credentials', [ServiceManagementWebController::class, 'rotateDatabase'])->name('service-management.databases.rotate');
        Route::post('service-management/caches', [ServiceManagementWebController::class, 'storeCache'])->name('service-management.caches.store');
        Route::delete('service-management/caches/{cacheInstance}', [ServiceManagementWebController::class, 'destroyCache'])->name('service-management.caches.destroy');
        Route::post('service-management/caches/{cacheInstance}/rotate-credentials', [ServiceManagementWebController::class, 'rotateCache'])->name('service-management.caches.rotate');
        Route::post('service-management/storage-buckets', [ServiceManagementWebController::class, 'storeStorageBucket'])->name('service-management.storage-buckets.store');
        Route::delete('service-management/storage-buckets/{storageBucket}', [ServiceManagementWebController::class, 'destroyStorageBucket'])->name('service-management.storage-buckets.destroy');
        Route::post('service-management/storage-buckets/{storageBucket}/rotate-credentials', [ServiceManagementWebController::class, 'rotateStorageBucket'])->name('service-management.storage-buckets.rotate');
        Route::post('environments/{environment}/service-bindings', [ServiceManagementWebController::class, 'storeBinding'])->name('environment-service-bindings.store');
        Route::delete('environments/{environment}/service-bindings/{serviceBinding}', [ServiceManagementWebController::class, 'destroyBinding'])->name('environment-service-bindings.destroy');
        Route::get('pipeline-runs/{pipelineRun}', [PipelineRunWebController::class, 'show'])->name('pipeline-runs.show');
        Route::get('deployments/{deployment}', [DeploymentWebController::class, 'show'])->name('deployments.show');
    });

    Route::resource('servers', ServerWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('servers/{server}/bootstrap', [ServerWebController::class, 'bootstrap'])->name('servers.bootstrap');

    Route::resource('clusters', ClusterWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('clusters/{cluster}/nodes', [ClusterWebController::class, 'addNode'])->name('clusters.add-node');
    Route::delete('clusters/{cluster}/nodes/{server}', [ClusterWebController::class, 'removeNode'])->name('clusters.remove-node');

    Route::get('activity', [ActivityWebController::class, 'index'])->name('activity.index');
    Route::get('runners', [RunnerWebController::class, 'index'])->name('runners.index');
});

require __DIR__.'/settings.php';
