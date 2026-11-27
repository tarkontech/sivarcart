<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tarkontech\SivarCart\Http\Controllers\Admin\AdminAssetController;
use Tarkontech\SivarCart\Http\Controllers\Admin\AdminPageController;
use Tarkontech\SivarCart\Http\Controllers\Admin\BrandController;
use Tarkontech\SivarCart\Http\Controllers\Admin\CategoryController;
use Tarkontech\SivarCart\Http\Controllers\Admin\LoginController;
use Tarkontech\SivarCart\Http\Controllers\Admin\OrderController;
use Tarkontech\SivarCart\Http\Controllers\Admin\OrderRefundController;
use Tarkontech\SivarCart\Http\Controllers\Admin\OrderStatusController;
use Tarkontech\SivarCart\Http\Controllers\Admin\PaymentMethodController;
use Tarkontech\SivarCart\Http\Controllers\Admin\ProductController;
use Tarkontech\SivarCart\Http\Controllers\Admin\RoleController;
use Tarkontech\SivarCart\Http\Controllers\Admin\SettingsController;
use Tarkontech\SivarCart\Http\Controllers\Admin\SetupController;
use Tarkontech\SivarCart\Http\Controllers\Admin\ShippingMethodController;
use Tarkontech\SivarCart\Http\Controllers\Admin\StaffController;
use Tarkontech\SivarCart\Http\Controllers\Admin\StoreMediaController;
use Tarkontech\SivarCart\Http\Middleware\HandleAdminInertiaRequests;

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

$prefix = (string) config('sivarcart.admin.prefix', 'sivarcart/admin');

$web = (array) config('sivarcart.admin.middleware', ['web']);

/*
| The assets and the sign in screen answer without the staff of the store
| being signed in, because the sign in screen needs the assets.
|
| The assets live in a folder with the version of the bundle. The pieces of
| the bundle ask for each other with a relative address, so the version of
| the folder travels with them: a browser that kept the bundle of a previous
| version never mixes it with the bundle of the one that is installed.
*/
Route::prefix($prefix)->middleware($web)->group(function (): void {
    Route::get('assets/{version}/admin.css', [AdminAssetController::class, 'css'])
        ->where('version', '[A-Za-z0-9]+')
        ->name('sivarcart.admin.assets.css');

    Route::get('assets/{version}/admin.js', [AdminAssetController::class, 'js'])
        ->where('version', '[A-Za-z0-9]+')
        ->name('sivarcart.admin.assets.js');

    /*
    | The bundle of the panel is split in pieces, one per page, and the browser
    | asks for them by their name.
    */
    Route::get('assets/{version}/{chunk}', [AdminAssetController::class, 'chunk'])
        ->where('version', '[A-Za-z0-9]+')
        ->where('chunk', '[A-Za-z0-9_.-]+\.(js|css)')
        ->name('sivarcart.admin.assets.chunk');

    Route::get('login', [LoginController::class, 'show'])
        ->middleware(HandleAdminInertiaRequests::class)
        ->name('sivarcart.admin.login');

    Route::post('login', [LoginController::class, 'store'])
        ->name('sivarcart.admin.login.store');

    Route::get('setup', [SetupController::class, 'show'])
        ->middleware(HandleAdminInertiaRequests::class)
        ->name('sivarcart.admin.setup');
});

Route::prefix($prefix)->middleware(array_merge($web, ['sivarcart.admin', HandleAdminInertiaRequests::class]))->group(function (): void {
    Route::get('/', [AdminPageController::class, 'index'])
        ->defaults('page', 'dashboard')
        ->name('sivarcart.admin.index');

    Route::get('orders/{order}', [OrderController::class, 'show'])
        ->middleware('store.permission:orders.view')
        ->defaults('page', 'orders')
        ->name('sivarcart.admin.orders.show');

    /*
    | The panel writes the state of an order and the settings of the store
    | through its own routes, so the panel never writes them by itself. The
    | permission of the store guards each one of them.
    */
    Route::post('orders/{order}/status', [OrderStatusController::class, 'update'])
        ->middleware('store.permission:orders.update')
        ->name('sivarcart.admin.orders.status');

    /*
    | A refund moves the axis of the payment of an order and a return says
    | that the units came back to the store, so the two have a route of their
    | own and not a move of the state.
    */
    Route::post('orders/{order}/refund', [OrderRefundController::class, 'refund'])
        ->middleware('store.permission:orders.update')
        ->name('sivarcart.admin.orders.refund');

    Route::post('orders/{order}/return', [OrderRefundController::class, 'returned'])
        ->middleware('store.permission:orders.update')
        ->name('sivarcart.admin.orders.return');

    Route::put('settings', [SettingsController::class, 'update'])
        ->middleware('store.permission:settings.update')
        ->name('sivarcart.admin.settings.update');

    /*
    | The logo and the favicon of the store are files, so they travel as
    | files and not as the body of a form.
    */
    Route::post('settings/logo', [StoreMediaController::class, 'logo'])
        ->middleware('store.permission:settings.update')
        ->name('sivarcart.admin.settings.logo');

    Route::post('settings/favicon', [StoreMediaController::class, 'favicon'])
        ->middleware('store.permission:settings.update')
        ->name('sivarcart.admin.settings.favicon');

    /*
    | The catalog: the products and their variants, the categories, the
    | brands, the shipping methods and the payment methods. The forms are
    | read with the permission of reading the catalog, and the writes are
    | written with the permission of writing it.
    */
    Route::get('products/create', [ProductController::class, 'create'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.products.create');

    Route::post('products', [ProductController::class, 'store'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.products.store');

    Route::get('products/{product}/edit', [ProductController::class, 'edit'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.products.edit');

    Route::put('products/{product}', [ProductController::class, 'update'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.products.update');

    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.products.destroy');

    Route::get('categories/create', [CategoryController::class, 'create'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.categories.create');

    Route::post('categories', [CategoryController::class, 'store'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.categories.store');

    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.categories.edit');

    Route::put('categories/{category}', [CategoryController::class, 'update'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.categories.update');

    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.categories.destroy');

    Route::get('brands/create', [BrandController::class, 'create'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.brands.create');

    Route::post('brands', [BrandController::class, 'store'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.brands.store');

    Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.brands.edit');

    Route::put('brands/{brand}', [BrandController::class, 'update'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.brands.update');

    Route::delete('brands/{brand}', [BrandController::class, 'destroy'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.brands.destroy');

    Route::get('shipping-methods/create', [ShippingMethodController::class, 'create'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.shipping-methods.create');

    Route::post('shipping-methods', [ShippingMethodController::class, 'store'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.shipping-methods.store');

    Route::get('shipping-methods/{method}/edit', [ShippingMethodController::class, 'edit'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.shipping-methods.edit');

    Route::put('shipping-methods/{method}', [ShippingMethodController::class, 'update'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.shipping-methods.update');

    Route::delete('shipping-methods/{method}', [ShippingMethodController::class, 'destroy'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.shipping-methods.destroy');

    Route::get('payment-methods/create', [PaymentMethodController::class, 'create'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.payment-methods.create');

    Route::post('payment-methods', [PaymentMethodController::class, 'store'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.payment-methods.store');

    Route::get('payment-methods/{method}/edit', [PaymentMethodController::class, 'edit'])
        ->middleware('store.permission:catalog.view')
        ->name('sivarcart.admin.payment-methods.edit');

    Route::put('payment-methods/{method}', [PaymentMethodController::class, 'update'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.payment-methods.update');

    Route::delete('payment-methods/{method}', [PaymentMethodController::class, 'destroy'])
        ->middleware('store.permission:catalog.update')
        ->name('sivarcart.admin.payment-methods.destroy');

    /*
    | The staff of the store are the users of the application, so the panel
    | writes their roles of the store and not the users themselves.
    */
    Route::post('staff', [StaffController::class, 'store'])
        ->middleware('store.permission:staff.update')
        ->name('sivarcart.admin.staff.store');

    Route::put('staff/{staff}', [StaffController::class, 'update'])
        ->middleware('store.permission:staff.update')
        ->name('sivarcart.admin.staff.update');

    Route::delete('staff/{staff}', [StaffController::class, 'destroy'])
        ->middleware('store.permission:staff.update')
        ->name('sivarcart.admin.staff.destroy');

    /*
    | The roles of the store: what every role grants.
    */
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('store.permission:roles.update')
        ->name('sivarcart.admin.roles.store');

    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('store.permission:roles.update')
        ->name('sivarcart.admin.roles.update');

    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('store.permission:roles.update')
        ->name('sivarcart.admin.roles.destroy');

    /*
    | The pages of the panel come last, so the routes above win. A page is
    | one the panel registry knows: the ones of the package and the ones the
    | host application registers.
    */
    Route::get('{page}', [AdminPageController::class, 'show'])
        ->name('sivarcart.admin.page');
});
