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

    // Bulk Operations
    Route::get('bulk-operations', function () {
        return Inertia::render('Ssl/BulkOperations/Index');
    })->name('bulk-operations');

    // Cache API endpoints that return JSON data
    Route::get('websites/{website}/details', [App\Http\Controllers\WebsiteController::class, 'details'])
        ->middleware('cache.api:180')
        ->name('websites.details');
    Route::get('websites/{website}/certificate-analysis', [App\Http\Controllers\WebsiteController::class, 'certificateAnalysis'])
        ->middleware('cache.api:600')
        ->name('websites.certificate-analysis');

    // Immediate check API endpoints (for real-time UI feedback)
    Route::post('websites/{website}/immediate-check', [App\Http\Controllers\WebsiteController::class, 'immediateCheck'])
        ->name('websites.immediate-check');
    Route::get('websites/{website}/check-status', [App\Http\Controllers\WebsiteController::class, 'checkStatus'])
        ->name('websites.check-status');

    // Historical monitoring data routes
    Route::get('websites/{website}/history', [App\Http\Controllers\WebsiteController::class, 'history'])
        ->middleware('cache.api:60')
        ->name('websites.history');
    Route::get('websites/{website}/statistics', [App\Http\Controllers\WebsiteController::class, 'statistics'])
        ->middleware('cache.api:120')
        ->name('websites.statistics');

    // Website transfer routes
    Route::post('websites/{website}/transfer-to-team', [App\Http\Controllers\WebsiteController::class, 'transferToTeam'])->name('websites.transfer-to-team');
    Route::post('websites/{website}/transfer-to-personal', [App\Http\Controllers\WebsiteController::class, 'transferToPersonal'])->name('websites.transfer-to-personal');
    Route::get('websites/{website}/transfer-options', [App\Http\Controllers\WebsiteController::class, 'getTransferOptions'])->name('websites.transfer-options');

    // Bulk transfer routes
    Route::post('websites/bulk-transfer-to-team', [App\Http\Controllers\WebsiteController::class, 'bulkTransferToTeam'])->name('websites.bulk-transfer-to-team');
    Route::post('websites/bulk-transfer-to-personal', [App\Http\Controllers\WebsiteController::class, 'bulkTransferToPersonal'])->name('websites.bulk-transfer-to-personal');

    // Website alerts routes
    Route::get('websites/{website}/alerts', [App\Http\Controllers\Settings\AlertsController::class, 'getWebsiteAlerts'])->name('websites.alerts');
});

// Analytics Routes
Route::get('/analytics', function () {
    return Inertia::render('Analytics/Index');
})->middleware(['auth', 'verified'])->name('analytics');

// Reports Routes
Route::get('/reports', function () {
    return Inertia::render('Reports/Index');
})->middleware(['auth', 'verified'])->name('reports');

// Alert Configuration Routes
Route::middleware(['auth', 'verified'])->prefix('alerts')->name('alerts.')->group(function () {
    Route::get('/', [App\Http\Controllers\AlertConfigurationController::class, 'index'])->name('index');
    Route::get('/notifications', [App\Http\Controllers\AlertConfigurationController::class, 'notifications'])->name('notifications');
    Route::get('/history', [App\Http\Controllers\AlertConfigurationController::class, 'history'])->name('history');
    Route::put('/{alertConfiguration}', [App\Http\Controllers\AlertConfigurationController::class, 'update'])->name('update');
    Route::post('/{alertConfiguration}/test', [App\Http\Controllers\AlertConfigurationController::class, 'testAlert'])->name('test');
    Route::post('/test-all', [App\Http\Controllers\AlertConfigurationController::class, 'testAllAlerts'])->name('test-all');
});

// Monitoring Report Routes
Route::middleware(['auth'])
    ->prefix('api/monitors/{monitor}/reports')
    ->name('api.monitors.reports.')
    ->group(function () {
        Route::get('/export-csv', [App\Http\Controllers\MonitoringReportController::class, 'exportCsv'])
            ->name('export-csv');
        Route::get('/summary', [App\Http\Controllers\MonitoringReportController::class, 'summary'])
            ->name('summary');
        Route::get('/daily-breakdown', [App\Http\Controllers\MonitoringReportController::class, 'dailyBreakdown'])
            ->name('daily-breakdown');
    });

// Monitor History API Routes
Route::middleware(['auth'])
    ->prefix('api/monitors/{monitor}')
    ->name('api.monitors.')
    ->group(function () {
        Route::get('/history', [App\Http\Controllers\API\MonitorHistoryController::class, 'history'])
            ->name('history');
        Route::get('/trends', [App\Http\Controllers\API\MonitorHistoryController::class, 'trends'])
            ->name('trends');
        Route::get('/summary', [App\Http\Controllers\API\MonitorHistoryController::class, 'summary'])
            ->name('summary');
        Route::get('/uptime-stats', [App\Http\Controllers\API\MonitorHistoryController::class, 'uptimeStats'])
            ->name('uptime-stats');
        Route::get('/ssl-info', [App\Http\Controllers\API\MonitorHistoryController::class, 'sslInfo'])
            ->name('ssl-info');
        Route::get('/recent-checks', [App\Http\Controllers\API\MonitorHistoryController::class, 'recentChecks'])
            ->name('recent-checks');
        Route::get('/ssl-expiration-trends', [App\Http\Controllers\API\MonitorHistoryController::class, 'sslExpirationTrends'])
            ->name('ssl-expiration-trends');
    });

// Team Management Routes
Route::middleware(['auth', 'verified'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/team', [App\Http\Controllers\TeamController::class, 'index'])->name('team');
    Route::post('/team', [App\Http\Controllers\TeamController::class, 'store'])->name('team.store');
    Route::get('/team/{team}', [App\Http\Controllers\TeamController::class, 'show'])->name('team.show');
    Route::put('/team/{team}', [App\Http\Controllers\TeamController::class, 'update'])->name('team.update');
    Route::delete('/team/{team}', [App\Http\Controllers\TeamController::class, 'destroy'])->name('team.destroy');
    Route::post('/team/{team}/invite', [App\Http\Controllers\TeamController::class, 'inviteMember'])->name('team.invite');
    Route::delete('/team/{team}/members/{user}', [App\Http\Controllers\TeamController::class, 'removeMember'])->name('team.members.remove');
    Route::patch('/team/{team}/members/{user}/role', [App\Http\Controllers\TeamController::class, 'updateMemberRole'])->name('team.members.role');
    Route::delete('/team/{team}/invitations/{invitation}', [App\Http\Controllers\TeamController::class, 'cancelInvitation'])->name('team.invitations.cancel');
    Route::post('/team/{team}/invitations/{invitation}/resend', [App\Http\Controllers\TeamController::class, 'resendInvitation'])->name('team.invitations.resend');
    Route::post('/team/{team}/transfer-ownership', [App\Http\Controllers\TeamController::class, 'transferOwnership'])->name('team.transfer-ownership');
});

// Team invitation routes (public access)
Route::prefix('team/invitations')->name('team.invitations.')->group(function () {
    Route::get('/{token}', [App\Http\Controllers\TeamInvitationController::class, 'show'])->name('accept');
    Route::post('/{token}/accept', [App\Http\Controllers\TeamInvitationController::class, 'accept'])->name('accept.existing');
    Route::post('/{token}/register', [App\Http\Controllers\TeamInvitationController::class, 'acceptWithRegistration'])->name('accept.new');
    Route::post('/{token}/decline', [App\Http\Controllers\TeamInvitationController::class, 'decline'])->name('decline');
});

// Debug Routes (Development & Testing Only)
Route::middleware(['auth', 'verified', 'debug.access'])->prefix('debug')->name('debug.')->group(function () {
    // SSL Overrides
    Route::get('/ssl-overrides', [App\Http\Controllers\Debug\SslOverridesController::class, 'index'])->name('ssl-overrides.index');
    Route::post('/ssl-overrides', [App\Http\Controllers\Debug\SslOverridesController::class, 'store'])->name('ssl-overrides.store');
    Route::put('/ssl-overrides/{id}', [App\Http\Controllers\Debug\SslOverridesController::class, 'update'])->name('ssl-overrides.update');
    Route::delete('/ssl-overrides/{id}', [App\Http\Controllers\Debug\SslOverridesController::class, 'destroy'])->name('ssl-overrides.destroy');
    Route::post('/ssl-overrides/bulk', [App\Http\Controllers\Debug\SslOverridesController::class, 'bulkStore'])->name('ssl-overrides.bulk-store');
    Route::delete('/ssl-overrides/bulk', [App\Http\Controllers\Debug\SslOverridesController::class, 'bulkDestroy'])->name('ssl-overrides.bulk-destroy');
    Route::post('/ssl-overrides/test', [App\Http\Controllers\Debug\SslOverridesController::class, 'testAlerts'])->name('ssl-overrides.test');

    // Alert Testing
    Route::get('/alerts', [App\Http\Controllers\Debug\AlertTestingController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/test-all', [App\Http\Controllers\Debug\AlertTestingController::class, 'testAllAlerts'])->name('alerts.test-all');
    Route::post('/alerts/test-ssl', [App\Http\Controllers\Debug\AlertTestingController::class, 'testSslAlerts'])->name('alerts.test-ssl');
    Route::post('/alerts/test-uptime', [App\Http\Controllers\Debug\AlertTestingController::class, 'testUptimeAlerts'])->name('alerts.test-uptime');
    Route::post('/alerts/test-response-time', [App\Http\Controllers\Debug\AlertTestingController::class, 'testResponseTimeAlerts'])->name('alerts.test-response-time');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
