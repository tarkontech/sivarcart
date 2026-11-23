<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SivarcartCore\SivarcartCore\Actions\DeliverOrder;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Events\OrderDelivered;
use SivarcartCore\SivarcartCore\Exceptions\InvalidOrderStatusTransition;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('delivers the order', function () {
    Event::fake([OrderDelivered::class]);

    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);

    app(DeliverOrder::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::DELIVERED);

    Event::assertDispatched(OrderDelivered::class, fn (OrderDelivered $event): bool => $event->order->is($order));
});

it('records the change of the status of the order', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);

    app(DeliverOrder::class)->execute($order, null, 'Delivered at the door');

    $history = $order->fresh()->statusHistories->last();

    expect($history->axis)->toBe(OrderHistoryAxis::STATUS)
        ->and($history->status)->toBe(OrderStatus::DELIVERED)
        ->and($history->previous_status)->toBe(OrderStatus::SHIPPED)
        ->and($history->note)->toBe('Delivered at the door');
});

it('does not deliver an order that was not shipped', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect(fn () => app(DeliverOrder::class)->execute($order))
        ->toThrow(InvalidOrderStatusTransition::class);

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});
