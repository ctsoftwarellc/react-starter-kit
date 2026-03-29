<?php

use App\Http\Controllers\Web\ActivityWebController;
use App\Http\Controllers\Web\ApplicationWebController;
use App\Http\Controllers\Web\ClusterWebController;
use App\Http\Controllers\Web\EnvironmentWebController;
use App\Http\Controllers\Web\GitConnectionWebController;
use App\Http\Controllers\Web\ProjectWebController;
use App\Http\Controllers\Web\ServerWebController;
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
    Route::get('projects/{project}/applications/create', [ApplicationWebController::class, 'create'])->name('applications.create');
    Route::post('projects/{project}/applications', [ApplicationWebController::class, 'store'])->name('applications.store');
    Route::get('applications/{application}', [ApplicationWebController::class, 'show'])->name('applications.show');
    Route::put('applications/{application}', [ApplicationWebController::class, 'update'])->name('applications.update');
    Route::delete('applications/{application}', [ApplicationWebController::class, 'destroy'])->name('applications.destroy');

    Route::post('applications/{application}/environments', [EnvironmentWebController::class, 'store'])->name('environments.store');
    Route::get('environments/{environment}', [EnvironmentWebController::class, 'show'])->name('environments.show');
    Route::put('environments/{environment}', [EnvironmentWebController::class, 'update'])->name('environments.update');
    Route::delete('environments/{environment}', [EnvironmentWebController::class, 'destroy'])->name('environments.destroy');
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

    Route::resource('servers', ServerWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('servers/{server}/bootstrap', [ServerWebController::class, 'bootstrap'])->name('servers.bootstrap');

    Route::resource('clusters', ClusterWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('clusters/{cluster}/nodes', [ClusterWebController::class, 'addNode'])->name('clusters.add-node');
    Route::delete('clusters/{cluster}/nodes/{server}', [ClusterWebController::class, 'removeNode'])->name('clusters.remove-node');

    Route::get('activity', [ActivityWebController::class, 'index'])->name('activity.index');
});

require __DIR__.'/settings.php';
