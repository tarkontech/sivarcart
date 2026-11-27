<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Tarkontech\SivarCart\Actions\ShipOrder;
use Tarkontech\SivarCart\Enums\OrderHistoryAxis;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Events\OrderShipped;
use Tarkontech\SivarCart\Exceptions\InvalidOrderStatusTransition;
use Tarkontech\SivarCart\Services\OrderService;
use Tarkontech\SivarCart\Tests\Support\CheckoutScenario;

it('ships the order', function () {
    Event::fake([OrderShipped::class]);

    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(ShipOrder::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::SHIPPED)
        ->and($order->fresh()->fulfilled_at)->not->toBeNull();

    Event::assertDispatched(OrderShipped::class, fn (OrderShipped $event): bool => $event->order->is($order));
});

it('ships an order that was being prepared', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::PROCESSING);

    app(ShipOrder::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::SHIPPED);
});

it('records the change of the status of the order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(ShipOrder::class)->execute($order, null, 'Handed to the courier');

    $history = $order->fresh()->statusHistories->first();

    expect($history->axis)->toBe(OrderHistoryAxis::STATUS)
        ->and($history->status)->toBe(OrderStatus::SHIPPED)
        ->and($history->previous_status)->toBe(OrderStatus::PENDING)
        ->and($history->note)->toBe('Handed to the courier');
});

it('keeps the moment the order was fulfilled', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(ShipOrder::class)->execute($order);

    $fulfilledAt = $order->fresh()->fulfilled_at;

    $order->transitionTo(OrderStatus::DELIVERED);

    expect($order->fresh()->fulfilled_at->timestamp)->toBe($fulfilledAt->timestamp);
});

it('does not ship an order that was already delivered', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);
    $order->transitionTo(OrderStatus::DELIVERED);

    expect(fn () => app(ShipOrder::class)->execute($order))
        ->toThrow(InvalidOrderStatusTransition::class);
});
