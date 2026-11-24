<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SivarcartCore\SivarcartCore\Actions\MarkOrderAsPaid;
use SivarcartCore\SivarcartCore\Actions\RefundOrder;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Events\OrderRefunded;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

/**
 * An order that was paid and can be refunded.
 */
$paidOrder = function (): array {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(MarkOrderAsPaid::class)->execute($order);

    return [$order, $scenario];
};

it('registers the refund of the order', function () use ($paidOrder) {
    Event::fake([OrderRefunded::class]);

    [$order] = $paidOrder();

    app(RefundOrder::class)->execute($order, 25.50, 'Producto devuelto');

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::REFUNDED)
        ->and($order->fresh()->status)->toBe(OrderStatus::REFUNDED)
        ->and($order->payments->first()->fresh()->status)->toBe(PaymentRecordStatus::REFUNDED)
        ->and((float) $order->payments->first()->fresh()->refunded_amount)->toBe(25.5);

    Event::assertDispatched(OrderRefunded::class);
});

it('gives the units back to the stock', function () use ($paidOrder) {
    [$order, $scenario] = $paidOrder();

    app(RefundOrder::class)->execute($order, 25.50);

    expect($scenario->variant->fresh()->stock_quantity)->toBe(25)
        ->and($scenario->variant->fresh()->reserved_quantity)->toBe(0);
});

it('keeps the order open when only a part of the payment is refunded', function () use ($paidOrder) {
    [$order] = $paidOrder();

    app(RefundOrder::class)->execute($order, 10.00);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PARTIALLY_REFUNDED)
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($order->payments->first()->fresh()->status)->toBe(PaymentRecordStatus::PARTIALLY_REFUNDED);
});

it('refuses a refund bigger than what was paid', function () use ($paidOrder) {
    [$order] = $paidOrder();

    expect(fn () => app(RefundOrder::class)->execute($order, 100.00))
        ->toThrow(SivarcartCoreException::class);
});

it('refuses a refund of no money', function () use ($paidOrder) {
    [$order] = $paidOrder();

    expect(fn () => app(RefundOrder::class)->execute($order, 0))
        ->toThrow(SivarcartCoreException::class);
});
