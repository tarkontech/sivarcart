<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SivarcartCore\SivarcartCore\Actions\PrepareOrder;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Events\OrderPrepared;
use SivarcartCore\SivarcartCore\Exceptions\InvalidOrderStatusTransition;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('starts the preparation of the order', function () {
    Event::fake([OrderPrepared::class]);

    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(PrepareOrder::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('records the change of the status of the order', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(PrepareOrder::class)->execute($order, null, 'En preparación');

    $history = $order->fresh()->statusHistories->first();

    expect($history->axis)->toBe(OrderHistoryAxis::STATUS)
        ->and($history->status)->toBe(OrderStatus::PROCESSING)
        ->and($history->previous_status)->toBe(OrderStatus::PENDING)
        ->and($history->note)->toBe('En preparación');
});

it('emits the event of the preparation', function () {
    Event::fake([OrderPrepared::class]);

    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(PrepareOrder::class)->execute($order);

    Event::assertDispatched(OrderPrepared::class, fn (OrderPrepared $event): bool => $event->order->is($order));
});

it('does not prepare an order that was cancelled', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $order->transitionTo(OrderStatus::CANCELLED);

    expect(fn () => app(PrepareOrder::class)->execute($order))
        ->toThrow(InvalidOrderStatusTransition::class);

    expect($order->fresh()->statusHistories)->toHaveCount(1);
});
