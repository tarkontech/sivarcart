<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SivarcartCore\SivarcartCore\Actions\CancelOrder;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Events\OrderCancelled;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('cancels the order', function () {
    Event::fake([OrderCancelled::class]);

    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order, 'El comprador se arrepintió');

    expect($order->fresh()->status)->toBe(OrderStatus::CANCELLED)
        ->and($order->fresh()->cancelled_at)->not->toBeNull();

    Event::assertDispatched(OrderCancelled::class, fn (OrderCancelled $event): bool => $event->reason === 'El comprador se arrepintió');
});

it('gives the reserved units back to the stock', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order);

    expect($scenario->variant->fresh()->reserved_quantity)->toBe(0)
        ->and($scenario->variant->fresh()->stock_quantity)->toBe(25)
        ->and($scenario->variant->fresh()->availableStock())->toBe(25);
});

it('cancels the payment that was never settled', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::guest(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order);

    expect($order->payments->first()->fresh()->status)->toBe(PaymentRecordStatus::CANCELLED);
});

it('records the change of the status of the order', function () {
    $scenario = CheckoutScenario::guest();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(CancelOrder::class)->execute($order, 'Sin stock');

    expect($order->fresh()->statusHistories)->toHaveCount(1)
        ->and($order->fresh()->statusHistories->first()->status)->toBe(OrderStatus::CANCELLED)
        ->and($order->fresh()->statusHistories->first()->note)->toBe('Sin stock');
});
