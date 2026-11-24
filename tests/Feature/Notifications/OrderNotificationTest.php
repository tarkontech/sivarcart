<?php

declare(strict_types=1);

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Support\Facades\Notification;
use SivarcartCore\SivarcartCore\Actions\CancelOrder;
use SivarcartCore\SivarcartCore\Actions\DeliverOrder;
use SivarcartCore\SivarcartCore\Actions\HandleWompiPayment;
use SivarcartCore\SivarcartCore\Actions\MarkOrderAsPaid;
use SivarcartCore\SivarcartCore\Actions\PlaceOrder;
use SivarcartCore\SivarcartCore\Actions\RefundOrder;
use SivarcartCore\SivarcartCore\Actions\ShipOrder;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Notifications\OrderCancelledNotification;
use SivarcartCore\SivarcartCore\Notifications\OrderDeliveredNotification;
use SivarcartCore\SivarcartCore\Notifications\OrderPaidNotification;
use SivarcartCore\SivarcartCore\Notifications\OrderPlacedNotification;
use SivarcartCore\SivarcartCore\Notifications\OrderRefundedNotification;
use SivarcartCore\SivarcartCore\Notifications\OrderShippedNotification;
use SivarcartCore\SivarcartCore\Notifications\PaymentFailedNotification;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;
use SivarcartCore\SivarcartCore\Tests\Support\WompiPayload;

/**
 * An order that was placed and is waiting for its payment.
 */
$placedOrder = function (): Order {
    $method = PaymentMethod::factory()->cashOnDelivery()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    return app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);
};

it('tells the buyer that the order was placed', function () {
    Notification::fake();

    $scenario = CheckoutScenario::buyer();

    $order = app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertSentOnDemand(
        OrderPlacedNotification::class,
        fn (OrderPlacedNotification $notification): bool => $notification->order->is($order)
            && $notification->recipient === $order->customer_email,
    );
});

it('tells the buyer that the order was paid', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(MarkOrderAsPaid::class)->execute($order);

    Notification::assertSentOnDemand(
        OrderPaidNotification::class,
        fn (OrderPaidNotification $notification): bool => $notification->order->is($order)
            && $notification->recipient === $order->customer_email,
    );
});

it('tells the buyer that the order was shipped', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(ShipOrder::class)->execute($order);

    Notification::assertSentOnDemand(
        OrderShippedNotification::class,
        fn (OrderShippedNotification $notification): bool => $notification->order->is($order),
    );
});

it('tells the buyer that the order was delivered', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(ShipOrder::class)->execute($order);
    app(DeliverOrder::class)->execute($order);

    Notification::assertSentOnDemand(
        OrderDeliveredNotification::class,
        fn (OrderDeliveredNotification $notification): bool => $notification->order->is($order),
    );
});

it('tells the buyer that the order was cancelled', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(CancelOrder::class)->execute($order, 'El comprador se arrepintió');

    Notification::assertSentOnDemand(
        OrderCancelledNotification::class,
        fn (OrderCancelledNotification $notification): bool => $notification->order->is($order),
    );
});

it('tells the buyer that the order was refunded', function () use ($placedOrder) {
    Notification::fake();

    $order = $placedOrder();

    app(MarkOrderAsPaid::class)->execute($order);
    app(RefundOrder::class)->execute($order, 25.50, 'Producto devuelto');

    Notification::assertSentOnDemand(
        OrderRefundedNotification::class,
        fn (OrderRefundedNotification $notification): bool => $notification->order->is($order)
            && $notification->amount === 25.50,
    );
});

it('tells the buyer that the payment did not go through', function () {
    Notification::fake();

    $method = PaymentMethod::factory()->wompi()->create();
    $scenario = CheckoutScenario::buyer(paymentMethodCode: $method->code);

    $order = app(OrderService::class)->place($scenario->cart, $scenario->data);

    app(HandleWompiPayment::class)->execute(
        WompiPayload::rejected($order->payments->first()->reference),
    );

    Notification::assertSentOnDemand(
        PaymentFailedNotification::class,
        fn (PaymentFailedNotification $notification): bool => $notification->order->is($order),
    );
});

it('survives the trip through the queue', function () {
    Notification::fake()->serializeAndRestore();

    $scenario = CheckoutScenario::buyer();

    $order = app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertSentOnDemand(
        OrderPlacedNotification::class,
        fn (OrderPlacedNotification $notification): bool => $notification->order->is($order)
            && $notification->recipient === $order->customer_email,
    );
});

it('writes to nobody when the order has no address', function () {
    Notification::fake();

    $scenario = CheckoutScenario::forCustomer(Customer::factory()->guest()->create());

    app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    Notification::assertNothingSent();
});

it('keeps the order when the mailer is down', function () {
    $this->app->bind(MailFactory::class, fn (): MailFactory => new class implements MailFactory
    {
        public function mailer($name = null)
        {
            throw new RuntimeException('The mailer is down.');
        }
    });

    $scenario = CheckoutScenario::buyer();

    $order = app(PlaceOrder::class)->execute($scenario->cart, $scenario->data);

    expect($order->fresh()->order_number)->not->toBeEmpty()
        ->and($order->fresh()->items)->toHaveCount(1);
});
