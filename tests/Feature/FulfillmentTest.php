<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Actions\MarkOrderAsPaid;
use SivarcartCore\SivarcartCore\Actions\ShipOrder;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Models\InventoryMovement;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;

it('does not move the stock when the order is shipped', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $before = $scenario->variant->fresh();

    app(ShipOrder::class)->execute($order);

    $variant = $scenario->variant->fresh();

    expect($variant->stock_quantity)->toBe($before->stock_quantity)
        ->and($variant->reserved_quantity)->toBe($before->reserved_quantity)
        ->and($variant->availableStock())->toBe($before->availableStock());

    // The units are still reserved: they become a sale when the order is paid.
    expect($variant->reserved_quantity)->toBe(1)
        ->and($variant->stock_quantity)->toBe(25)
        ->and($variant->availableStock())->toBe(24);
});

it('does not add a row to the ledger of the stock when the order is shipped', function () {
    $scenario = CheckoutScenario::buyer();

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    $before = InventoryMovement::query()->count();

    app(ShipOrder::class)->execute($order);

    expect(InventoryMovement::query()->count())->toBe($before);
});

it('ships an order that is not paid yet', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(ShipOrder::class)->execute($order);

    expect($order->fresh()->status)->toBe(OrderStatus::SHIPPED)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::UNPAID);
});

it('does not record anything when the order is marked as paid twice', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(MarkOrderAsPaid::class)->execute($order);
    app(MarkOrderAsPaid::class)->execute($order);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PAID)
        ->and($order->fresh()->statusHistories)->toHaveCount(1);
});

it('keeps the changes of both axes in the history of the order', function () {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(MarkOrderAsPaid::class)->execute($order);
    app(ShipOrder::class)->execute($order);

    $history = $order->fresh()->statusHistories;

    expect($history)->toHaveCount(2)
        ->and($order->statusHistories()->forAxis(OrderHistoryAxis::PAYMENT)->count())->toBe(1)
        ->and($order->statusHistories()->forAxis(OrderHistoryAxis::STATUS)->count())->toBe(1);

    expect($order->statusHistories()->forAxis(OrderHistoryAxis::PAYMENT)->first()->payment_status)->toBe(PaymentStatus::PAID)
        ->and($order->statusHistories()->forAxis(OrderHistoryAxis::STATUS)->first()->status)->toBe(OrderStatus::SHIPPED);
});
