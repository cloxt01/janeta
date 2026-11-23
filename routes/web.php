
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OntController;
use App\Http\Controllers\DashboardController;
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

    Route::prefix('dashboard/olts')
        ->name('olts.')
        ->group(function () {

            Route::get('/', [DashboardController::class, 'olts'])
                ->middleware('permission:olt.view')
                ->name('index');

            Route::get('/ports', [DashboardController::class, 'oltPorts'])
                ->middleware('permission:olt.view')
                ->name('ports');

            Route::post('/', [DashboardController::class, 'storeOlt'])
                ->middleware('permission:olt.create')
                ->name('store');

            Route::put('/{olt}', [DashboardController::class, 'updateOlt'])
                ->middleware('permission:olt.update')
                ->name('update');

            Route::delete('/{olt}', [DashboardController::class, 'destroyOlt'])
                ->middleware('permission:olt.delete')
                ->name('destroy');

            Route::post('/{olt}/test', [DashboardController::class, 'testOlt'])
                ->middleware('permission:olt.test')
                ->name('test');

            Route::post('/test-all', [DashboardController::class, 'testAllOlts'])
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

            Route::get('/', [DashboardController::class, 'whitelist'])
                ->middleware('permission:telegram.view')
                ->name('index');

            Route::post('/', [DashboardController::class, 'storeWhitelist'])
                ->middleware('permission:telegram.manage')
                ->name('store');

            Route::put('/{entry}', [DashboardController::class, 'updateWhitelist'])
                ->middleware('permission:telegram.manage')
                ->name('update');

            Route::delete('/{entry}', [DashboardController::class, 'destroyWhitelist'])
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

            Route::get('/', [DashboardController::class, 'users'])
                ->middleware('permission:users.view')
                ->name('index');

            Route::post('/', [DashboardController::class, 'storeUser'])
                ->middleware('permission:users.create')
                ->name('store');

            Route::put('/{user}', [DashboardController::class, 'updateUser'])
                ->middleware('permission:users.update')
                ->name('update');

            Route::delete('/{user}', [DashboardController::class, 'destroyUser'])
                ->middleware('permission:users.delete')
                ->name('destroy');
        });


    /*
    |--------------------------------------------------------------------------
    | AUDIT LOGS
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard/audit-logs', [DashboardController::class, 'auditLogs'])
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
