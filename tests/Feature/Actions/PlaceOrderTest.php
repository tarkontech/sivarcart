<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Tarkontech\SivarCart\Actions\PlaceOrder;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Events\OrderPlaced;
use Tarkontech\SivarCart\Exceptions\InsufficientStock;
use Tarkontech\SivarCart\Exceptions\InvalidCartState;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Tests\Support\CheckoutScenario;

it('places the order of a cart', function () {
    Event::fake([OrderPlaced::class]);

    $scenario = CheckoutScenario::buyer();

    $order = app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    expect($order->items)->toHaveCount(1)
        ->and($order->status)->toBe(OrderStatus::PENDING)
        ->and($scenario->cart->fresh()->isConverted())->toBeTrue();

    Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event): bool => $event->order->is($order));
});

it('reserves the stock of the order it places', function () {
    Event::fake([OrderPlaced::class]);

    $scenario = CheckoutScenario::buyer();

    app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    expect($scenario->variant->fresh()->reserved_quantity)->toBe(1)
        ->and($scenario->variant->fresh()->availableStock())->toBe(24);
});

it('writes nothing when the order cannot be placed', function () {
    Event::fake([OrderPlaced::class]);

    $scenario = CheckoutScenario::buyer();
    $scenario->variant->update(['stock_quantity' => 0]);

    expect(fn () => app(PlaceOrder::class)->execute($scenario->cart, $scenario->data))
        ->toThrow(InsufficientStock::class);

    expect(Order::query()->count())->toBe(0)
        ->and($scenario->cart->fresh()->isConverted())->toBeFalse();

    Event::assertNotDispatched(OrderPlaced::class);
});

it('does not place the same order twice', function () {
    Event::fake([OrderPlaced::class]);

    $scenario = CheckoutScenario::buyer();
    $action = app(PlaceOrder::class);

    $action->execute($scenario->cart, $scenario->data);

    expect(fn () => $action->execute($scenario->cart->fresh(), $scenario->data))
        ->toThrow(InvalidCartState::class);

    expect(Order::query()->count())->toBe(1);
});
