<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Enums\ProductStatus;
use SivarcartCore\SivarcartCore\Enums\ProductType;
use SivarcartCore\SivarcartCore\Exceptions\InvalidOrderStatusTransition;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductOption;
use SivarcartCore\SivarcartCore\Models\ProductOptionValue;
use SivarcartCore\SivarcartCore\Models\ProductVariant;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use SivarcartCore\SivarcartCore\Models\StoreUser;

it('gives every product a sellable unit', function () {
    $product = Product::factory()->create();

    expect($product->variants)->toHaveCount(1);
    expect($product->defaultVariant())->not->toBeNull();
    expect($product->defaultVariant()->is_default)->toBeTrue();
});

it('does not add a second variant to a product that already has one', function () {
    $product = Product::factory()->create();

    ProductVariant::factory()->create(['product_id' => $product->getKey()]);

    expect($product->fresh()->variants)->toHaveCount(2);
});

it('links a product to its variants and to their option values', function () {
    $product = Product::factory()->variable()->create();

    $option = ProductOption::factory()->create(['product_id' => $product->getKey()]);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->getKey()]);

    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);
    $variant->optionValues()->attach($value->getKey());

    expect($product->variants)->toHaveCount(1);
    expect($variant->fresh()->optionValues)->toHaveCount(1);
    expect($product->optionValues)->toHaveCount(1);
    expect($variant->fresh()->optionLabel())->toBe($value->value);
});

it('reports the price range of the active variants', function () {
    $product = Product::factory()->variable()->create();

    ProductVariant::factory()->create(['product_id' => $product->getKey(), 'price' => 10]);
    ProductVariant::factory()->create(['product_id' => $product->getKey(), 'price' => 25]);
    ProductVariant::factory()->create(['product_id' => $product->getKey(), 'price' => 99, 'is_active' => false]);

    expect($product->fresh()->priceRange())->toBe(['min' => 10.0, 'max' => 25.0]);
});

it('casts the money, the flags and the states of a model', function () {
    $product = Product::factory()->create([
        'type' => ProductType::VARIABLE,
        'status' => ProductStatus::PUBLISHED,
        'is_featured' => true,
        'weight' => 1.5,
        'meta' => ['origin' => 'web'],
    ]);

    expect($product->type)->toBe(ProductType::VARIABLE);
    expect($product->status)->toBe(ProductStatus::PUBLISHED);
    expect($product->is_featured)->toBeTrue();
    expect((float) $product->weight)->toBe(1.5);
    expect($product->meta)->toBe(['origin' => 'web']);
});

it('records the status changes of an order', function () {
    $order = Order::factory()->create();

    $order->transitionTo(OrderStatus::PAID, note: 'Pago recibido');

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::PAID);
    expect($order->payment_status)->toBe(PaymentStatus::PAID);
    expect($order->paid_at)->not->toBeNull();
    expect($order->statusHistories)->toHaveCount(1);
    expect($order->statusHistories->first()->previous_status)->toBe(OrderStatus::PENDING);
    expect($order->statusHistories->first()->note)->toBe('Pago recibido');
});

it('refuses a transition the order cannot make', function () {
    $order = Order::factory()->create(['status' => OrderStatus::CANCELLED]);

    expect(fn () => $order->transitionTo(OrderStatus::DELIVERED))
        ->toThrow(InvalidOrderStatusTransition::class);

    expect($order->fresh()->statusHistories)->toHaveCount(0);
});

it('refuses to leave a terminal status', function () {
    expect(OrderStatus::CANCELLED->isTerminal())->toBeTrue();
    expect(OrderStatus::REFUNDED->isTerminal())->toBeTrue();
    expect(OrderStatus::PENDING->isTerminal())->toBeFalse();
});

it('gives the staff of the store its roles', function () {
    $user = StoreUser::factory()->create();
    $role = StoreRole::factory()->create(['permissions' => ['orders.view']]);

    $user->roles()->attach($role);

    expect($user->fresh()->roles)->toHaveCount(1);
    expect($user->fresh()->roles->first()->permissions)->toBe(['orders.view']);
});
