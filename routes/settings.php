<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SshKeySettingsController;
use App\Http\Controllers\Settings\TokenSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/ssh-keys', [SshKeySettingsController::class, 'index'])->name('ssh-keys.index');
    Route::post('settings/ssh-keys', [SshKeySettingsController::class, 'store'])->name('ssh-keys.store');
    Route::delete('settings/ssh-keys/{sshKey}', [SshKeySettingsController::class, 'destroy'])->name('ssh-keys.destroy');

    Route::get('settings/tokens', [TokenSettingsController::class, 'index'])->name('tokens.index');
    Route::post('settings/tokens', [TokenSettingsController::class, 'store'])->name('tokens.store');
    Route::delete('settings/tokens/{personalAccessToken}', [TokenSettingsController::class, 'destroy'])->name('tokens.destroy');
});
