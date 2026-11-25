<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\AdminAssetController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\AdminPageController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\LoginController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\OrderController;
use SivarcartCore\SivarcartCore\Http\Controllers\Admin\SetupController;

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
*/

$prefix = (string) config('sivarcart-core.admin.prefix', 'sivarcart/admin');

$web = (array) config('sivarcart-core.admin.middleware', ['web']);

/*
| The stylesheet and the sign in screen answer without the staff of the
| store being signed in, because the sign in screen needs the stylesheet.
*/
Route::prefix($prefix)->middleware($web)->group(function (): void {
    Route::get('assets/admin.css', AdminAssetController::class)
        ->name('sivarcart-core.admin.assets.css');

    Route::get('login', [LoginController::class, 'show'])
        ->name('sivarcart-core.admin.login');

    Route::post('login', [LoginController::class, 'store'])
        ->name('sivarcart-core.admin.login.store');

    Route::get('setup', [SetupController::class, 'show'])
        ->name('sivarcart-core.admin.setup');
});

Route::prefix($prefix)->middleware(array_merge($web, ['sivarcart.admin']))->group(function (): void {
    Route::get('/', [AdminPageController::class, 'index'])
        ->defaults('page', 'dashboard')
        ->name('sivarcart-core.admin.index');

    Route::get('orders/{order}', [OrderController::class, 'show'])
        ->middleware('store.permission:orders.view')
        ->defaults('page', 'orders')
        ->name('sivarcart-core.admin.orders.show');

    /*
    | The pages of the panel come last, so the routes above win. A page is
    | one the panel registry knows: the ones of the package and the ones the
    | host application registers.
    */
    Route::get('{page}', [AdminPageController::class, 'show'])
        ->name('sivarcart-core.admin.page');
});
