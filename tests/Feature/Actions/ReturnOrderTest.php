<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SivarcartCore\SivarcartCore\Actions\ReturnOrder;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Events\OrderReturned;
use SivarcartCore\SivarcartCore\Exceptions\InvalidOrderStatusTransition;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('registers the return of the order', function () {
    Event::fake([OrderReturned::class]);

    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);

    app(ReturnOrder::class)->execute($order, null, 'The buyer refused it');

    expect($order->fresh()->status)->toBe(OrderStatus::RETURNED);

    Event::assertDispatched(
        OrderReturned::class,
        fn (OrderReturned $event): bool => $event->order->is($order) && $event->reason === 'The buyer refused it',
    );
});

it('returns an order that was delivered', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);
    $order->transitionTo(OrderStatus::DELIVERED);

    app(ReturnOrder::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::RETURNED);
});

it('records the change of the status of the order', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);

    app(ReturnOrder::class)->execute($order, null, 'The buyer refused it');

    $history = $order->fresh()->statusHistories->last();

    expect($history->axis)->toBe(OrderHistoryAxis::STATUS)
        ->and($history->status)->toBe(OrderStatus::RETURNED)
        ->and($history->previous_status)->toBe(OrderStatus::SHIPPED)
        ->and($history->note)->toBe('The buyer refused it');
});

it('refunds an order that came back', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::SHIPPED);

    app(ReturnOrder::class)->execute($order);

    expect($order->status->canTransitionTo(OrderStatus::REFUNDED))->toBeTrue();
});

it('does not return an order that was not shipped', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    expect(fn () => app(ReturnOrder::class)->execute($order))
        ->toThrow(InvalidOrderStatusTransition::class);
});
