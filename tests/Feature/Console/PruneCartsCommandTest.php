<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\CartItem;
use SivarcartCore\SivarcartCore\Models\Order;

it('deletes the carts that expired', function () {
    $expired = Cart::factory()->create(['expires_at' => now()->subDay()]);
    $alive = Cart::factory()->create(['expires_at' => now()->addDay()]);

    $this->artisan('sivarcart:prune-carts')->assertSuccessful();

    expect(Cart::query()->whereKey($expired->getKey())->exists())->toBeFalse()
        ->and(Cart::query()->whereKey($alive->getKey())->exists())->toBeTrue();
});

it('deletes the items of the carts that expired', function () {
    $expired = Cart::factory()->create(['expires_at' => now()->subDay()]);
    $item = CartItem::factory()->create(['cart_id' => $expired->getKey()]);

    $this->artisan('sivarcart:prune-carts')->assertSuccessful();

    expect(CartItem::query()->whereKey($item->getKey())->exists())->toBeFalse();
});

it('does not touch a cart that is still alive', function () {
    $alive = Cart::factory()->create(['expires_at' => now()->addDay()]);
    $item = CartItem::factory()->create(['cart_id' => $alive->getKey()]);

    $this->artisan('sivarcart:prune-carts')->assertSuccessful();

    expect(Cart::query()->whereKey($alive->getKey())->exists())->toBeTrue()
        ->and(CartItem::query()->whereKey($item->getKey())->exists())->toBeTrue();
});

it('does not touch a cart that never expires', function () {
    $cart = Cart::factory()->create(['expires_at' => null]);

    $this->artisan('sivarcart:prune-carts')->assertSuccessful();

    expect(Cart::query()->whereKey($cart->getKey())->exists())->toBeTrue();
});

it('deletes a cart that was already converted into an order', function () {
    $converted = Cart::factory()->create([
        'expires_at' => now()->subDay(),
        'converted_at' => now()->subWeek(),
    ]);
    $order = Order::factory()->create();

    $this->artisan('sivarcart:prune-carts')->assertSuccessful();

    expect(Cart::query()->whereKey($converted->getKey())->exists())->toBeFalse()
        ->and(Order::query()->whereKey($order->getKey())->exists())->toBeTrue();
});

it('prunes nothing when there is nothing to prune', function () {
    Cart::factory()->create(['expires_at' => now()->addDay()]);

    $this->artisan('sivarcart:prune-carts')
        ->expectsOutputToContain('0')
        ->assertSuccessful();
});

it('reports how many carts it deleted', function () {
    Cart::factory()->count(3)->create(['expires_at' => now()->subDay()]);

    $this->artisan('sivarcart:prune-carts')
        ->expectsOutputToContain('3')
        ->assertSuccessful();

    expect(Cart::query()->count())->toBe(0);
});
