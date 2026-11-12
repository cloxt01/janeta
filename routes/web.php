
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OntController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OltActivityController;

// Root
Route::get('/', fn () => redirect()->route('dashboard'));

// Guest
Route::middleware('guest')->group(function () {
    Route::get('/login', [DashboardController::class, 'login'])->name('login');
    Route::post('/login', [DashboardController::class, 'authenticate'])
        ->name('login.authenticate');
});

// Authenticated
Route::middleware('auth')->group(function () {

    Route::post('/logout', [DashboardController::class, 'logout'])
        ->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/dashboard/ont', [DashboardController::class, 'ontLookup'])
        ->name('dashboard.ont');

    // ONT API
    Route::prefix('api/v1/ont')->name('api.ont.')->group(function () {

        Route::get('/lookup', [OntController::class, 'lookup'])
            ->name('lookup');

        Route::get('/check-desc', [OntController::class, 'checkDescription'])
            ->name('check-desc');

        Route::get('/version', [OntController::class, 'version'])
            ->name('version');
    });

    // ADMIN ONLY
    Route::middleware('admin')->group(function () {

        // OLT Activity Monitoring
        Route::get('/dashboard/olt-activity', [OltActivityController::class, 'index'])
            ->name('olt-activity');

        // Route::get('/dashboard/olt-activity/data', [OltActivityController::class, 'data'])
        //     ->name('olt-activity.data');

        // OLT
        Route::get('/dashboard/olts', [DashboardController::class, 'olts'])
            ->name('olts');

        Route::get('/dashboard/olts/ports', [DashboardController::class, 'oltPorts'])
            ->name('olts.ports');

        Route::post('/dashboard/olts', [DashboardController::class, 'storeOlt'])
            ->name('olts.store');

        Route::put('/dashboard/olts/{olt}', [DashboardController::class, 'updateOlt'])
            ->name('olts.update');

        Route::delete('/dashboard/olts/{olt}', [DashboardController::class, 'destroyOlt'])
            ->name('olts.destroy');

        Route::post('/dashboard/olts/{olt}/test', [DashboardController::class, 'testOlt'])
            ->name('olts.test');

        Route::post('/dashboard/olts/test-all', [DashboardController::class, 'testAllOlts'])
            ->name('olts.test-all');

        // Telegram
        Route::get('/dashboard/whitelist', [DashboardController::class, 'whitelist'])
            ->name('whitelist');

        Route::post('/dashboard/whitelist', [DashboardController::class, 'storeWhitelist'])
            ->name('whitelist.store');

        Route::put('/dashboard/whitelist/{entry}', [DashboardController::class, 'updateWhitelist'])
            ->name('whitelist.update');

        Route::delete('/dashboard/whitelist/{entry}', [DashboardController::class, 'destroyWhitelist'])
            ->name('whitelist.destroy');

        // Users
        Route::get('/dashboard/users', [DashboardController::class, 'users'])
            ->name('users');

        Route::post('/dashboard/users', [DashboardController::class, 'storeUser'])
            ->name('users.store');

        Route::put('/dashboard/users/{user}', [DashboardController::class, 'updateUser'])
            ->name('users.update');

        Route::delete('/dashboard/users/{user}', [DashboardController::class, 'destroyUser'])
            ->name('users.destroy');

        // Audit logs
        Route::get('/dashboard/audit-logs', [DashboardController::class, 'auditLogs'])
            ->name('audit-logs');
    });
});
