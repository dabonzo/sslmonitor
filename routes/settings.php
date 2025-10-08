<?php

use App\Http\Controllers\Settings\AlertsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');


    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    Route::post('settings/two-factor', [TwoFactorAuthenticationController::class, 'store'])
        ->name('two-factor.store');

    Route::post('settings/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])
        ->name('two-factor.confirm');

    Route::delete('settings/two-factor', [TwoFactorAuthenticationController::class, 'destroy'])
        ->name('two-factor.destroy');

    Route::get('settings/two-factor/recovery-codes', [TwoFactorAuthenticationController::class, 'recoveryCodes'])
        ->name('two-factor.recovery-codes');

    Route::get('settings/alerts', [AlertsController::class, 'edit'])->name('settings.alerts.edit');
    Route::post('settings/alerts', [AlertsController::class, 'store'])->name('settings.alerts.store');
    Route::put('settings/alerts/{alertConfiguration}', [AlertsController::class, 'update'])->name('settings.alerts.update');
    Route::delete('settings/alerts/{alertConfiguration}', [AlertsController::class, 'destroy'])->name('settings.alerts.destroy');
});
