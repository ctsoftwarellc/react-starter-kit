<?php

use App\Http\Controllers\Web\ActivityWebController;
use App\Http\Controllers\Web\ProjectWebController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('projects', ProjectWebController::class);

    Route::get('activity', [ActivityWebController::class, 'index'])->name('activity.index');
});

require __DIR__.'/settings.php';
