<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use SivarcartCore\SivarcartCore\Http\Controllers\Api\AccountController;
use SivarcartCore\SivarcartCore\Http\Controllers\Api\CartController;
use SivarcartCore\SivarcartCore\Http\Controllers\Api\CatalogController;
use SivarcartCore\SivarcartCore\Http\Controllers\Api\CheckoutController;
use SivarcartCore\SivarcartCore\Http\Controllers\Api\OrderController;

/*
|--------------------------------------------------------------------------
| Store API
|--------------------------------------------------------------------------
|
| The API of the store: the catalog is public, and the cart, the checkout,
| the orders and the account of the buyer need a buyer signed in with the
| guard of the package ("sivarcart").
|
| The host application does not have to publish this file: the package
| registers it. The prefix, the middleware group and the rate limit come
| from the "api" section of the configuration of the package.
|
*/

/*
| The buyer signs in with a session, so the routes that need a buyer start
| the session of the application. The check of the CSRF is left out on
| purpose: the API is not a form of a browser, and the cookie of the session
| is "SameSite=Lax", so another site cannot send it in a request that writes.
*/
$session = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
];

$middleware = (array) config('sivarcart-core.api.middleware', ['api']);
$middleware[] = 'sivarcart.api';

Route::prefix((string) config('sivarcart-core.api.prefix', 'api/sivarcart'))
    ->middleware($middleware)
    ->group(function () use ($session): void {
        /*
        | The catalog is public: anybody can read the products of the store,
        | and these routes do not need a session.
        */
        Route::get('products', [CatalogController::class, 'index'])
            ->name('sivarcart-core.api.products.index');

        Route::get('products/{product}', [CatalogController::class, 'show'])
            ->name('sivarcart-core.api.products.show');

        Route::get('categories', [CatalogController::class, 'categories'])
            ->name('sivarcart-core.api.categories.index');

        Route::get('brands', [CatalogController::class, 'brands'])
            ->name('sivarcart-core.api.brands.index');

        /*
        | The account of the buyer. The registration and the login do not need
        | a buyer signed in: they are the ones that start the session.
        */
        Route::middleware($session)->group(function (): void {
            Route::post('account/register', [AccountController::class, 'register'])
                ->name('sivarcart-core.api.account.register');

            Route::post('account/login', [AccountController::class, 'login'])
                ->name('sivarcart-core.api.account.login');

            Route::post('account/logout', [AccountController::class, 'logout'])
                ->name('sivarcart-core.api.account.logout');

            Route::post('account/password/email', [AccountController::class, 'sendPasswordResetLink'])
                ->name('sivarcart-core.api.account.password.email');

            Route::middleware('sivarcart.buyer')->group(function (): void {
                Route::get('account', [AccountController::class, 'show'])
                    ->name('sivarcart-core.api.account.show');

                Route::patch('account', [AccountController::class, 'update'])
                    ->name('sivarcart-core.api.account.update');

                Route::put('account/password', [AccountController::class, 'changePassword'])
                    ->name('sivarcart-core.api.account.password.update');

                Route::get('account/addresses', [AccountController::class, 'addresses'])
                    ->name('sivarcart-core.api.account.addresses.index');
            });
        });

        /*
        | The cart of the buyer. The cart always belongs to a buyer, so every
        | route of the group needs a session and a buyer signed in.
        */
        Route::middleware(array_merge($session, ['sivarcart.buyer']))->group(function (): void {
            Route::get('cart', [CartController::class, 'show'])
                ->name('sivarcart-core.api.cart.show');

            Route::post('cart/items', [CartController::class, 'addItem'])
                ->name('sivarcart-core.api.cart.items.store');

            Route::patch('cart/items/{item}', [CartController::class, 'updateItem'])
                ->name('sivarcart-core.api.cart.items.update');

            Route::delete('cart/items/{item}', [CartController::class, 'removeItem'])
                ->name('sivarcart-core.api.cart.items.destroy');

            Route::post('cart/coupon', [CartController::class, 'applyCoupon'])
                ->name('sivarcart-core.api.cart.coupon.store');

            Route::delete('cart/coupon', [CartController::class, 'removeCoupon'])
                ->name('sivarcart-core.api.cart.coupon.destroy');

            Route::delete('cart', [CartController::class, 'clear'])
                ->name('sivarcart-core.api.cart.destroy');

            /*
            | The checkout: the options of the store, the price of the
            | shipping and the order itself.
            */
            Route::get('checkout/options', [CheckoutController::class, 'options'])
                ->name('sivarcart-core.api.checkout.options');

            Route::post('checkout/shipping-quote', [CheckoutController::class, 'shippingQuote'])
                ->name('sivarcart-core.api.checkout.shipping-quote');

            Route::post('checkout', [CheckoutController::class, 'store'])
                ->name('sivarcart-core.api.checkout.store');

            /*
            | The orders of the buyer.
            */
            Route::get('orders', [OrderController::class, 'index'])
                ->name('sivarcart-core.api.orders.index');

            Route::get('orders/{order}', [OrderController::class, 'show'])
                ->name('sivarcart-core.api.orders.show');

            Route::post('orders/{order}/payment', [OrderController::class, 'payment'])
                ->name('sivarcart-core.api.orders.payment.store');
        });
    });
