<?php

use App\Http\Controllers\Web\ActivityWebController;
use App\Http\Controllers\Web\ClusterWebController;
use App\Http\Controllers\Web\ProjectWebController;
use App\Http\Controllers\Web\ServerWebController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('projects', ProjectWebController::class);

    Route::resource('servers', ServerWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('servers/{server}/bootstrap', [ServerWebController::class, 'bootstrap'])->name('servers.bootstrap');

    Route::resource('clusters', ClusterWebController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('clusters/{cluster}/nodes', [ClusterWebController::class, 'addNode'])->name('clusters.add-node');
    Route::delete('clusters/{cluster}/nodes/{server}', [ClusterWebController::class, 'removeNode'])->name('clusters.remove-node');

    Route::get('activity', [ActivityWebController::class, 'index'])->name('activity.index');
});

require __DIR__.'/settings.php';
