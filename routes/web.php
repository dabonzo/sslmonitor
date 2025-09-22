<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', [App\Http\Controllers\SslDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// SSL Website Management Routes
Route::middleware(['auth', 'verified'])->prefix('ssl')->name('ssl.')->group(function () {
    Route::resource('websites', App\Http\Controllers\WebsiteController::class);
    Route::post('websites/{website}/check', [App\Http\Controllers\WebsiteController::class, 'check'])->name('websites.check');
    Route::delete('websites/bulk-destroy', [App\Http\Controllers\WebsiteController::class, 'bulkDestroy'])->name('websites.bulk-destroy');
    Route::post('websites/bulk-check', [App\Http\Controllers\WebsiteController::class, 'bulkCheck'])->name('websites.bulk-check');
    Route::get('websites/{website}/details', [App\Http\Controllers\WebsiteController::class, 'details'])->name('websites.details');
    Route::get('websites/{website}/certificate-analysis', [App\Http\Controllers\WebsiteController::class, 'certificateAnalysis'])->name('websites.certificate-analysis');
});

// Alert Configuration Routes
Route::middleware(['auth', 'verified'])->prefix('alerts')->name('alerts.')->group(function () {
    Route::get('/', [App\Http\Controllers\AlertConfigurationController::class, 'index'])->name('index');
    Route::put('/{alertConfiguration}', [App\Http\Controllers\AlertConfigurationController::class, 'update'])->name('update');
    Route::post('/{alertConfiguration}/test', [App\Http\Controllers\AlertConfigurationController::class, 'testAlert'])->name('test');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
