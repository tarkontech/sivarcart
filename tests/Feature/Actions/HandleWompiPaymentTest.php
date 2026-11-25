<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use SivarcartCore\SivarcartCore\Actions\HandleWompiPayment;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Events\OrderPaid;
use SivarcartCore\SivarcartCore\Events\PaymentFailed;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Models\PaymentTransaction;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;
use SivarcartCore\SivarcartCore\Tests\Support\WompiPayload;

/**
 * An order that is waiting for a payment of Wompi.
 */
$orderWaitingForWompi = function (): Order {
    $method = PaymentMethod::factory()->wompi()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    return app(OrderService::class)->place($scenario->cart, $scenario->data);
};

it('marks the order as paid when the payment was approved', function () use ($orderWaitingForWompi) {
    Event::fake([OrderPaid::class, PaymentFailed::class]);

    $order = $orderWaitingForWompi();
    $payment = $order->payments->first();

    app(HandleWompiPayment::class)->execute(WompiPayload::approved($payment->reference));

    expect($payment->fresh()->status)->toBe(PaymentRecordStatus::PAID)
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($order->fresh()->payment_status->value)->toBe('paid');

    Event::assertDispatched(OrderPaid::class);
});

it('records the transaction Wompi reported', function () use ($orderWaitingForWompi) {
    $order = $orderWaitingForWompi();
    $payment = $order->payments->first();

    app(HandleWompiPayment::class)->execute(WompiPayload::approved($payment->reference));

    $transaction = PaymentTransaction::query()->firstOrFail();

    expect($transaction->payment_id)->toBe($payment->getKey())
        ->and($transaction->gateway_transaction_id)->toBe('txn-1')
        ->and($transaction->gateway_payment_attempt_id)->toBe('attempt-1')
        ->and($transaction->idempotency_key)->toBe('txn-1:attempt-1')
        ->and($transaction->merchant_reference)->toBe($payment->reference)
        ->and($transaction->is_approved)->toBeTrue()
        ->and($transaction->is_production)->toBeTrue()
        ->and($transaction->status->value)->toBe('approved');
});

it('turns the reserved units into a sale when the payment is applied', function () {
    $method = PaymentMethod::factory()->wompi()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(HandleWompiPayment::class)->execute(WompiPayload::approved($order->payments->first()->reference));

    expect($scenario->variant->fresh()->stock_quantity)->toBe(24)
        ->and($scenario->variant->fresh()->reserved_quantity)->toBe(0);
});

it('does not apply the same webhook twice', function () use ($orderWaitingForWompi) {
    $order = $orderWaitingForWompi();
    $payment = $order->payments->first();

    $action = app(HandleWompiPayment::class);

    $action->execute(WompiPayload::approved($payment->reference));
    $action->execute(WompiPayload::approved($payment->reference));

    expect(PaymentTransaction::query()->count())->toBe(1)
        ->and($payment->fresh()->status)->toBe(PaymentRecordStatus::PAID);
});

it('does not mark the order as paid when the transaction was only a test', function () use ($orderWaitingForWompi) {
    Event::fake([OrderPaid::class, PaymentFailed::class]);

    $order = $orderWaitingForWompi();
    $payment = $order->payments->first();

    app(HandleWompiPayment::class)->execute(
        WompiPayload::approved($payment->reference, production: false),
    );

    expect($payment->fresh()->status)->toBe(PaymentRecordStatus::FAILED)
        ->and($payment->fresh()->failure_reason)->toContain('prueba')
        ->and($order->fresh()->status)->toBe(OrderStatus::PENDING)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::FAILED);

    Event::assertDispatched(PaymentFailed::class);
    Event::assertNotDispatched(OrderPaid::class);
});

it('leaves the payment of the order failed when the payment was rejected', function () use ($orderWaitingForWompi) {
    $order = $orderWaitingForWompi();

    app(HandleWompiPayment::class)->execute(WompiPayload::rejected($order->payments->first()->reference));

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::FAILED);
});

it('records the failure of the payment in the history of the order', function () use ($orderWaitingForWompi) {
    $order = $orderWaitingForWompi();

    app(HandleWompiPayment::class)->execute(WompiPayload::rejected($order->payments->first()->reference));

    $history = $order->fresh()->statusHistories()->forAxis(OrderHistoryAxis::PAYMENT)->first();

    expect($history->payment_status)->toBe(PaymentStatus::FAILED)
        ->and($history->previous_payment_status)->toBe(PaymentStatus::UNPAID);
});

it('does not fail the payment of an order that was already paid', function () use ($orderWaitingForWompi) {
    $order = $orderWaitingForWompi();

    app(HandleWompiPayment::class)->execute(WompiPayload::approved($order->payments->first()->reference));

    app(HandleWompiPayment::class)->execute(
        WompiPayload::rejected($order->payments->first()->reference),
    );

    // The payment of the order does not go back: the money arrived. The
    // delivery is still applied, so the webhook is not lost.
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::PAID)
        ->and(PaymentTransaction::query()->count())->toBe(2);
});

it('keeps the reservation of an order whose payment failed', function () {
    $method = PaymentMethod::factory()->wompi()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(HandleWompiPayment::class)->execute(
        WompiPayload::rejected($order->payments->first()->reference),
    );

    expect($scenario->variant->fresh()->reserved_quantity)->toBe(1)
        ->and($scenario->variant->fresh()->stock_quantity)->toBe(25)
        ->and($order->fresh()->payments->first()->fresh()->status)->toBe(PaymentRecordStatus::FAILED);
});

it('ignores a webhook of a payment it does not know', function () {
    app(HandleWompiPayment::class)->execute(WompiPayload::approved('PAGO-DESCONOCIDO'));

    expect(PaymentTransaction::query()->count())->toBe(0);
});

it('ignores a webhook without a merchant reference', function () {
    app(HandleWompiPayment::class)->execute(WompiPayload::withoutReference());

    expect(PaymentTransaction::query()->count())->toBe(0);
});
