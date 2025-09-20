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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
