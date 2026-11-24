<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SivarcartCore\SivarcartCore\Actions\MarkOrderAsPaid;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Events\OrderPaid;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('marks the order as paid', function () {
    Event::fake([OrderPaid::class]);

    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(MarkOrderAsPaid::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($order->fresh()->payment_status->value)->toBe('paid')
        ->and($order->fresh()->paid_at)->not->toBeNull();

    Event::assertDispatched(OrderPaid::class);
});

it('marks the pending payment of the order as paid', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(MarkOrderAsPaid::class)->execute($order);

    expect($order->payments->first()->fresh()->status)->toBe(PaymentRecordStatus::PAID)
        ->and($order->payments->first()->fresh()->paid_at)->not->toBeNull();
});

it('turns the reserved units into a sale', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(MarkOrderAsPaid::class)->execute($order);

    expect($scenario->variant->fresh()->stock_quantity)->toBe(24)
        ->and($scenario->variant->fresh()->reserved_quantity)->toBe(0);
});

it('marks a payment the store collected on its own as paid', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);
    $payment = $order->payments->first();

    app(MarkOrderAsPaid::class)->execute($order, $payment);

    expect($payment->fresh()->status)->toBe(PaymentRecordStatus::PAID)
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('marks an order without a payment as paid', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(MarkOrderAsPaid::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($order->fresh()->payments)->toHaveCount(0);
});
