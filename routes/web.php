<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\EmailSettings;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TeamManagement;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('websites', function () {
    return view('websites');
})->middleware(['auth', 'verified'])->name('websites');

Route::get('websites/{website}', function (\App\Models\Website $website) {
    Gate::authorize('view', $website);
    return view('website-details', compact('website'));
})->middleware(['auth', 'verified'])->name('websites.show');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
    Route::get('settings/email', EmailSettings::class)->name('settings.email');
    Route::get('settings/team', TeamManagement::class)->name('settings.team');
});

require __DIR__.'/auth.php';
