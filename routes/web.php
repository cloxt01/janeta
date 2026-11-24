<?php

use App\Http\Controllers\WhitelistController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\LocationAdminController;
use App\Http\Controllers\Router\MikrotikController;
use App\Http\Controllers\UserLocationController;
use App\Http\Controllers\OntController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\OltActivityController;
use App\Http\Controllers\AccessControlController;

// Root
Route::get('/', fn () => redirect()->route('dashboard'));

// Guest
Route::middleware('guest')->group(function () {
    Route::get('/login', [DashboardController::class, 'login'])
        ->name('login');

    Route::post('/login', [DashboardController::class, 'authenticate'])
        ->name('login.authenticate');
});

// Authenticated
Route::middleware('auth')->group(function () {

    // Logout
    Route::post('/logout', [DashboardController::class, 'logout'])
        ->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    // ONT Lookup Page
    Route::get('/dashboard/ont', [DashboardController::class, 'ontLookup'])
        ->middleware('permission:ont.lookup')
        ->name('dashboard.ont');


    /*
    |--------------------------------------------------------------------------
    | ONT PROCESSING
    |--------------------------------------------------------------------------
    */

    Route::prefix('portal/ont')
        ->name('portal.ont.')
        ->middleware('permission:ont.processing')
        ->group(function () {

            Route::get('/processing/{uuid}', [OntController::class, 'processing'])
                ->name('processing');

            Route::get('/request-status/{uuid}', [OntController::class, 'requestStatus'])
                ->name('request-status');

            Route::get('/result/{uuid}', [OntController::class, 'result'])
                ->name('result');
        });


    /*
    |--------------------------------------------------------------------------
    | ONT API
    |--------------------------------------------------------------------------
    */

    Route::prefix('api/v1/ont')
        ->name('api.ont.')
        ->group(function () {

            Route::post('/control', [OntController::class, 'control'])
                ->middleware('permission:ont.control')
                ->name('control');

            Route::get('/lookup', [OntController::class, 'lookup'])
                ->middleware('permission:ont.lookup')
                ->name('lookup');

            Route::get('/check-desc', [OntController::class, 'checkDescription'])
                ->middleware('permission:ont.lookup')
                ->name('check-desc');

            Route::get('/version', [OntController::class, 'version'])
                ->middleware('permission:ont.lookup')
                ->name('version');
        });


    /*
    |--------------------------------------------------------------------------
    | OLT ACTIVITY
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard/olt-activity', [OltActivityController::class, 'index'])
        ->middleware('permission:olt.activity.view')
        ->name('olt-activity');


    /*
    |--------------------------------------------------------------------------
    | OLT MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::prefix('dashboard/router')
        ->name('router.')
        ->group(function () {

            // Group khusus MikroTik
            Route::prefix('mikrotik')
                ->name('mikrotik.')
                ->controller(MikrotikController::class)
                ->group(function () {
                    Route::get('/', 'index')->middleware('permission:router-mikrotik.view')->name('index');
                    Route::post('/', 'store')->middleware('permission:router-mikrotik.store')->name('store');
                    Route::get('/{mikrotik}', 'show')->middleware('permission:router-mikrotik.show')->name('show');
                    Route::put('/{mikrotik}', 'update')->middleware('permission:router-mikrotik.update')->middleware('permission:router-mikrotik.view')->name('update');
                    Route::post('/capture', 'capture')->middleware('permission:router-mikrotik.capture')->name('capture');
                    Route::delete('/{mikrotik}', 'destroy')->middleware('permission:router-mikrotik.destroy')->name('destroy');
                    Route::post('/test-all', 'testAll')->middleware('permission:router-mikrotik.test')->name('test-all');
                    Route::post('/{mikrotik}/test-connection', 'testConnection')->middleware('permission:router-mikrotik.test')->name('test');
                });

            // Nanti tinggal tambah group vendor lain di sini, misalnya:
            // Route::prefix('cisco')
            //     ->name('cisco.')
            //     ->controller(CiscoRouterController::class)
            //     ->group(function () {
            //         Route::get('/', 'index')->name('index');
            //     });

        });

    Route::prefix('dashboard/olts')
        ->name('olts.')
        ->group(function () {

            Route::get('/', [OltController::class, 'index'])
                ->middleware('permission:olt.view')
                ->name('index');

            Route::get('/ports', [OltController::class, 'ports'])
                ->middleware('permission:olt.view')
                ->name('ports');

            Route::post('/', [OltController::class, 'store'])
                ->middleware('permission:olt.create')
                ->name('store');

            Route::put('/{olt}', [OltController::class, 'update'])
                ->middleware('permission:olt.update')
                ->name('update');

            Route::delete('/{olt}', [OltController::class, 'destroy'])
                ->middleware('permission:olt.delete')
                ->name('destroy');

            Route::post('/{olt}/test', [OltController::class, 'test'])
                ->middleware('permission:olt.test')
                ->name('test');

            Route::post('/test-all', [OltController::class, 'testAll'])
                ->middleware('permission:olt.test')
                ->name('test-all');
        });


    /*
    |--------------------------------------------------------------------------
    | TELEGRAM WHITELIST
    |--------------------------------------------------------------------------
    */

    Route::prefix('dashboard/whitelist')
        ->name('whitelist.')
        ->group(function () {

            Route::get('/', [WhitelistController::class, 'index'])
                ->middleware('permission:telegram.view')
                ->name('index');

            Route::post('/', [WhitelistController::class, 'store'])
                ->middleware('permission:telegram.manage')
                ->name('store');

            Route::put('/{entry}', [WhitelistController::class, 'update'])
                ->middleware('permission:telegram.manage')
                ->name('update');

            Route::delete('/{entry}', [WhitelistController::class, 'destroy'])
                ->middleware('permission:telegram.manage')
                ->name('destroy');
        });


    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT
    |--------------------------------------------------------------------------
    */

    Route::prefix('dashboard/users')
        ->name('users.')
        ->group(function () {

            Route::get('/', [UserController::class, 'index'])
                ->middleware('permission:users.view')
                ->name('index');

            Route::post('/', [UserController::class, 'store'])
                ->middleware('permission:users.create')
                ->name('store');

            Route::put('/{user}', [UserController::class, 'update'])
                ->middleware('permission:users.update')
                ->name('update');

            Route::delete('/{user}', [UserController::class, 'destroy'])
                ->middleware('permission:users.delete')
                ->name('destroy');
        });


    /*
    |--------------------------------------------------------------------------
    | AUDIT LOGS
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard/audit-logs', [AuditController::class, 'logs'])
        ->middleware('permission:audit.view')
        ->name('audit-logs');


    /*
    |--------------------------------------------------------------------------
    | ACCESS CONTROL
    |--------------------------------------------------------------------------
    */

    Route::prefix('dashboard/access')
        ->name('access.')
        ->group(function () {

            Route::get('/', [AccessControlController::class, 'index'])
                ->middleware('permission:access.view')
                ->name('index');

            // Role management
            Route::post('/roles', [AccessControlController::class, 'storeRole'])
                ->middleware('permission:access.manage')
                ->name('roles.store');

            Route::put('/roles/{role}', [AccessControlController::class, 'updateRole'])
                ->middleware('permission:access.manage')
                ->name('roles.update');

            Route::delete('/roles/{role}', [AccessControlController::class, 'destroyRole'])
                ->middleware('permission:access.manage')
                ->name('roles.destroy');

            // Role permissions
            Route::put('/roles/{role}/permissions', [AccessControlController::class, 'updatePermissions'])
                ->middleware('permission:access.manage')
                ->name('roles.permissions');

            // User roles
            Route::put('/users/{user}/roles', [AccessControlController::class, 'updateUserRoles'])
                ->middleware('permission:access.manage')
                ->name('users.roles');
        });

});

// ===================== A. SISI USER (di luar location.required) =====================

Route::get('/location/permission', fn () => view('location.permission'))
    ->name('location.permission');

Route::post('/location/authorize', [UserLocationController::class, 'authorizeLocation'])
    ->middleware(['permission:location.track', 'throttle:10,1'])
    ->name('location.authorize');

Route::prefix('dashboard/location')->name('location.')->group(function () {

    // Update GPS berkala. Klien mengirim tiap ~5 detik (= 12/menit); 30/menit memberi ruang.
    Route::post('/update', [UserLocationController::class, 'update'])
        ->middleware(['permission:location.track', 'throttle:30,1'])
        ->name('update');

    Route::post('/stop', [UserLocationController::class, 'stop'])
        ->middleware('permission:location.track')
        ->name('stop');
});

// ===================== B. SISI ADMIN (di dalam location.required) =====================

Route::prefix('dashboard/location')->name('location.')->group(function () {

    Route::get('/live', [LocationAdminController::class, 'index'])
        ->middleware('permission:location.view')
        ->name('live');

    Route::post('/revoke', [UserLocationController::class, 'revoke'])
        ->middleware('permission:location.track')
        ->name('revoke');

    Route::get('/live/data', [LocationAdminController::class, 'data'])
        ->middleware('permission:location.view')
        ->name('live.data');

    Route::get('/history/{user}', [LocationAdminController::class, 'history'])
        ->middleware('permission:location.history')
        ->name('history');
});
