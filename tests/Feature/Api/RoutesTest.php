<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Route;

it('registers the routes of the store API', function () {
    expect(Route::has('sivarcart-core.api.products.index'))->toBeTrue()
        ->and(Route::has('sivarcart-core.api.products.show'))->toBeTrue()
        ->and(Route::has('sivarcart-core.api.categories.index'))->toBeTrue()
        ->and(Route::has('sivarcart-core.api.brands.index'))->toBeTrue()
        ->and(Route::has('sivarcart-core.api.account.register'))->toBeTrue()
        ->and(Route::has('sivarcart-core.api.account.login'))->toBeTrue()
        ->and(Route::has('sivarcart-core.api.cart.show'))->toBeTrue()
        ->and(Route::has('sivarcart-core.api.checkout.store'))->toBeTrue()
        ->and(Route::has('sivarcart-core.api.orders.index'))->toBeTrue();
});

it('mounts the API under the configured prefix', function () {
    $route = Route::getRoutes()->getByName('sivarcart-core.api.products.index');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('api/sivarcart/products')
        ->and($route->methods())->toContain('GET');
});

it('gives the API the middleware of the package', function () {
    $middleware = Route::getRoutes()
        ->getByName('sivarcart-core.api.cart.show')
        ->gatherMiddleware();

    expect($middleware)->toContain('sivarcart.api')
        ->and($middleware)->toContain('sivarcart.buyer');
});

it('leaves the catalog of the store open', function () {
    $middleware = Route::getRoutes()
        ->getByName('sivarcart-core.api.products.index')
        ->gatherMiddleware();

    expect($middleware)->toContain('sivarcart.api')
        ->and($middleware)->not->toContain('sivarcart.buyer');
});

it('keeps the route of the webhook of Wompi', function () {
    expect(Route::has('sivarcart-core.wompi.webhook'))->toBeTrue();

    $route = Route::getRoutes()->getByName('sivarcart-core.wompi.webhook');

    expect($route->uri())->toBe('webhooks/wompi')
        ->and($route->gatherMiddleware())->toContain('wompi.webhook');
});

it('registers the limiter of the API with the name of the package', function () {
    expect(app(RateLimiter::class)->limiter('sivarcart'))->not->toBeNull();
});
