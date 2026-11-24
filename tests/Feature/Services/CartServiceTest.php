<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Enums\CouponType;
use SivarcartCore\SivarcartCore\Exceptions\CouponNotApplicable;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\Coupon;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\Store;
use SivarcartCore\SivarcartCore\Services\CartService;

it('creates the cart of a customer and reuses it', function () {
    $customer = Customer::factory()->create();
    $carts = app(CartService::class);

    $cart = $carts->findOrCreate($customer->getKey());

    expect($cart->customer_id)->toBe($customer->getKey())
        ->and($carts->findOrCreate($customer->getKey())->is($cart))->toBeTrue();
});

it('gives a cart of its own to every customer', function () {
    $carts = app(CartService::class);

    $first = $carts->findOrCreate(Customer::factory()->create()->getKey());
    $second = $carts->findOrCreate(Customer::factory()->create()->getKey());

    expect($first->is($second))->toBeFalse();
});

it('uses the currency of the store', function () {
    Store::factory()->create(['currency' => 'USD']);
    $customer = Customer::factory()->create();

    $cart = app(CartService::class)->findOrCreate($customer->getKey());

    expect($cart->currency)->toBe('USD');
});

it('adds the units of a variant that is already in the cart', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $carts->addItem($cart, $variant, 2);
    $carts->addItem($cart, $variant, 3);

    expect($cart->fresh()->items)->toHaveCount(1)
        ->and($cart->fresh()->items->first()->quantity)->toBe(5);
});

it('keeps the name, the sku and the price of the variant in the item', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $cart = Cart::factory()->create();

    $item = app(CartService::class)->addItem($cart, $variant, 1);

    expect($item->sku)->toBe($variant->sku)
        ->and((float) $item->unit_price)->toBe(25.5);
});

it('refuses an item with no units', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $cart = Cart::factory()->create();

    expect(fn () => app(CartService::class)->addItem($cart, $variant, 0))
        ->toThrow(SivarcartCoreException::class);
});

it('recalculates the totals of the cart from its items', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $carts->addItem($cart, $variant, 2);

    $fresh = $cart->fresh();

    expect((float) $fresh->subtotal)->toBe(51.0)
        ->and((float) $fresh->total)->toBe(51.0)
        ->and((float) $fresh->tax_total)->toBe(5.87)
        ->and($fresh->item_count)->toBe(2);
});

it('changes the units of an item', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $item = $carts->addItem($cart, $variant, 2);
    $carts->updateQuantity($cart, $item, 4);

    expect($cart->fresh()->items->first()->quantity)->toBe(4)
        ->and((float) $cart->fresh()->total)->toBe(102.0);
});

it('takes an item out of the cart when it has no units left', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $item = $carts->addItem($cart, $variant, 2);
    $carts->updateQuantity($cart, $item, 0);

    expect($cart->fresh()->items)->toHaveCount(0)
        ->and((float) $cart->fresh()->total)->toBe(0.0);
});

it('removes an item from the cart', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $item = $carts->addItem($cart, $variant, 2);
    $carts->removeItem($cart, $item);

    expect($cart->fresh()->items)->toHaveCount(0)
        ->and($cart->fresh()->item_count)->toBe(0);
});

it('applies a coupon to the cart', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $coupon = Coupon::factory()->percentage(10)->create(['code' => 'DIEZ']);
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $carts->addItem($cart, $variant, 2);
    $carts->applyCoupon($cart, 'DIEZ');

    $fresh = $cart->fresh();

    expect($fresh->coupon_id)->toBe($coupon->getKey())
        ->and((float) $fresh->discount_total)->toBe(5.1)
        ->and((float) $fresh->total)->toBe(45.9);
});

it('refuses a coupon that does not exist', function () {
    $cart = Cart::factory()->create();

    expect(fn () => app(CartService::class)->applyCoupon($cart, 'NO-EXISTE'))
        ->toThrow(CouponNotApplicable::class);
});

it('refuses a coupon that cannot be used', function () {
    $coupon = Coupon::factory()->create(['code' => 'EXPIRADO', 'is_active' => false]);
    $cart = Cart::factory()->create();

    expect(fn () => app(CartService::class)->applyCoupon($cart, 'EXPIRADO'))
        ->toThrow(CouponNotApplicable::class);
});

it('removes the coupon of the cart', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $coupon = Coupon::factory()->percentage(10)->create(['code' => 'DIEZ']);
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $carts->addItem($cart, $variant, 2);
    $carts->applyCoupon($cart, 'DIEZ');
    $carts->removeCoupon($cart);

    expect($cart->fresh()->coupon_id)->toBeNull()
        ->and((float) $cart->fresh()->discount_total)->toBe(0.0)
        ->and((float) $cart->fresh()->total)->toBe(51.0);
});

it('empties the cart', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $coupon = Coupon::factory()->create(['code' => 'DIEZ']);
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $carts->addItem($cart, $variant, 2);
    $carts->applyCoupon($cart, 'DIEZ');
    $carts->clear($cart);

    $fresh = $cart->fresh();

    expect($fresh->items)->toHaveCount(0)
        ->and($fresh->coupon_id)->toBeNull()
        ->and((float) $fresh->total)->toBe(0.0);
});

it('does not give the shipping for free with a coupon that is not about the shipping', function () {
    $variant = Product::factory()->create()->defaultVariant();
    $coupon = Coupon::factory()->create(['type' => CouponType::FIXED, 'value' => 5, 'code' => 'CINCO']);
    $cart = Cart::factory()->create();
    $carts = app(CartService::class);

    $carts->addItem($cart, $variant, 1);
    $carts->applyCoupon($cart, 'CINCO');

    expect((float) $cart->fresh()->shipping_total)->toBe(0.0)
        ->and((float) $cart->fresh()->discount_total)->toBe(5.0);
});
