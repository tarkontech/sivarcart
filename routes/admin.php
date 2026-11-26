<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\AdminAssetController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\AdminPageController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\LoginController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\OrderController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\OrderStatusController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\SettingsController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\SetupController;
use SivarcartCore\SivarcartCore\Http\Middleware\HandleAdminInertiaRequests;

/*
|--------------------------------------------------------------------------
| Administration panel
|--------------------------------------------------------------------------
|
| The routes of the panel are registered by the package, so the application
| does not have to publish them. The prefix and the middleware come from the
| "admin" section of the configuration of the package.
|
| The panel uses the users, the guard and the session of the host
| application: it does not authenticate anybody on its own. The sign in
| screen of the panel is only the screen of that guard.
|
| The panel is an Inertia application of its own. Its middleware runs after
| the middleware of the application, so the props of the panel are the ones
| that reach its pages, and an application that already has an Inertia of its
| own keeps it untouched.
|
*/

$prefix = (string) config('sivarcart-core.admin.prefix', 'sivarcart/admin');

$web = (array) config('sivarcart-core.admin.middleware', ['web']);

/*
| The assets and the sign in screen answer without the staff of the store
| being signed in, because the sign in screen needs the assets.
*/
Route::prefix($prefix)->middleware($web)->group(function (): void {
    Route::get('assets/admin.css', [AdminAssetController::class, 'css'])
        ->name('sivarcart-core.admin.assets.css');

    Route::get('assets/admin.js', [AdminAssetController::class, 'js'])
        ->name('sivarcart-core.admin.assets.js');

    /*
    | The bundle of the panel is split in pieces, one per page, and the browser
    | asks for them by their name.
    */
    Route::get('assets/{chunk}', [AdminAssetController::class, 'chunk'])
        ->where('chunk', '[A-Za-z0-9_.-]+\.(js|css)')
        ->name('sivarcart-core.admin.assets.chunk');

    Route::get('login', [LoginController::class, 'show'])
        ->middleware(HandleAdminInertiaRequests::class)
        ->name('sivarcart-core.admin.login');

    Route::post('login', [LoginController::class, 'store'])
        ->name('sivarcart-core.admin.login.store');

    Route::get('setup', [SetupController::class, 'show'])
        ->middleware(HandleAdminInertiaRequests::class)
        ->name('sivarcart-core.admin.setup');
});

Route::prefix($prefix)->middleware(array_merge($web, ['sivarcart.admin', HandleAdminInertiaRequests::class]))->group(function (): void {
    Route::get('/', [AdminPageController::class, 'index'])
        ->defaults('page', 'dashboard')
        ->name('sivarcart-core.admin.index');

    Route::get('orders/{order}', [OrderController::class, 'show'])
        ->middleware('store.permission:orders.view')
        ->defaults('page', 'orders')
        ->name('sivarcart-core.admin.orders.show');

    /*
    | The panel writes the state of an order and the settings of the store
    | through its own routes, so the panel never writes them by itself. The
    | permission of the store guards each one of them.
    */
    Route::post('orders/{order}/status', [OrderStatusController::class, 'update'])
        ->middleware('store.permission:orders.update')
        ->name('sivarcart-core.admin.orders.status');

    Route::put('settings', [SettingsController::class, 'update'])
        ->middleware('store.permission:settings.update')
        ->name('sivarcart-core.admin.settings.update');

    /*
    | The pages of the panel come last, so the routes above win. A page is
    | one the panel registry knows: the ones of the package and the ones the
    | host application registers.
    */
    Route::get('{page}', [AdminPageController::class, 'show'])
        ->name('sivarcart-core.admin.page');
});
