<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SivarcartCore\SivarcartCore\Actions\CancelOrder;
use SivarcartCore\SivarcartCore\Actions\MarkOrderAsPaid;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Events\OrderCancelled;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('cancels the order', function () {
    Event::fake([OrderCancelled::class]);

    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order, 'El comprador se arrepintió');

    expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED)
        ->and($order->fresh()->cancelled_at)->not->toBeNull();

    Event::assertDispatched(OrderCancelled::class, fn (OrderCancelled $event): bool => $event->reason === 'El comprador se arrepintió');
});

it('gives the reserved units back to the stock', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order);

    expect($scenario->variant->fresh()->reserved_quantity)->toBe(0)
        ->and($scenario->variant->fresh()->stock_quantity)->toBe(25)
        ->and($scenario->variant->fresh()->availableStock())->toBe(25);
});

it('cancels the payment that was never settled', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order);

    expect($order->payments->first()->fresh()->status)->toBe(PaymentRecordStatus::CANCELLED);
});

it('cancels the payment of the order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::CANCELLED);
});

it('records the cancellation of the payment in the history of the order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order);

    $history = $order->fresh()->statusHistories()->forAxis(OrderHistoryAxis::PAYMENT)->first();

    expect($history->payment_status)->toBe(PaymentStatus::CANCELLED)
        ->and($history->previous_payment_status)->toBe(PaymentStatus::UNPAID);
});

it('keeps the payment of an order that was already paid', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(MarkOrderAsPaid::class)->execute($order);

    app(CancelOrder::class)->execute($order);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

it('records the change of the status of the order', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order, 'Sin stock');

    $history = $order->fresh()->statusHistories()->forAxis(OrderHistoryAxis::STATUS)->first();

    // The order has one row per axis: the status of the order and the state
    // of its payment.
    expect($order->fresh()->statusHistories)->toHaveCount(2)
        ->and($history->status)->toBe(OrderStatus::CANCELLED)
        ->and($history->note)->toBe('Sin stock');
});
