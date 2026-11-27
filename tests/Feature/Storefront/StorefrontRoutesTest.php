<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use SivarcartCore\SivarcartCore\Http\Middleware\HandleStorefrontInertiaRequests;
use SivarcartCore\SivarcartCore\Storefront\StorefrontBundle;

it('registers the routes of the store', function () {
    expect(Route::has('sivarcart-core.storefront.home'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.products.index'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.products.show'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.cart.show'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.cart.items.store'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.checkout.show'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.checkout.store'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.orders.index'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.orders.show'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.account.login'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.account.register'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.account.password.reset'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.assets.css'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.assets.js'))->toBeTrue()
        ->and(Route::has('sivarcart-core.storefront.assets.chunk'))->toBeTrue();
});

it('mounts the store under the configured prefix', function () {
    $route = Route::getRoutes()->getByName('sivarcart-core.storefront.products.index');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('tienda/productos')
        ->and($route->methods())->toContain('GET');
});

it('gives the store the middleware of the package', function () {
    $middleware = Route::getRoutes()
        ->getByName('sivarcart-core.storefront.products.index')
        ->gatherMiddleware();

    expect($middleware)->toContain('web')
        ->and($middleware)->toContain(HandleStorefrontInertiaRequests::class);
});

it('asks for a buyer signed in in the pages of the cart', function () {
    $middleware = Route::getRoutes()
        ->getByName('sivarcart-core.storefront.cart.show')
        ->gatherMiddleware();

    expect($middleware)->toContain('sivarcart.storefront.buyer');
});

it('sends the shopper that is not signed in to the screen of entry', function () {
    $this->get('/tienda/carrito')
        ->assertRedirect(route('sivarcart-core.storefront.account.login').'?redirect='.rawurlencode(url('/tienda/carrito')));
});

it('remembers where the shopper was going', function () {
    $this->get('/tienda/carrito')
        ->assertRedirect(route('sivarcart-core.storefront.account.login').'?redirect='.rawurlencode(url('/tienda/carrito')));
});

it('shows the home of the store', function () {
    $this->get('/tienda')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Home')
            ->has('store')
            ->has('cart')
            ->has('categories')
            ->has('text')
            ->where('store.currency', 'USD')
            ->where('cart.count', 0),
        );
});

it('mounts the store in its own element', function () {
    $response = $this->get('/tienda');

    expect($response->getContent())
        ->toContain('id="sivarcart-storefront"')
        ->toContain('/tienda/assets/'.StorefrontBundle::version().'/storefront.css')
        ->toContain('/tienda/assets/'.StorefrontBundle::version().'/storefront.js');
});
